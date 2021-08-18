<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Models\DeviceToken;
use App\Models\Otp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Auth;
use DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Socialite;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Notifications\PasswordResetOTPNotification;

class AuthController extends Controller
{
    public function register(Request $request){
       
        // Validations
        $rules = [
            'name'     => 'required',
            'email'    => 'required|unique:users|email',
            'password' => 'required',
            // 'isAdmin' => 'required'
        ];
        
        $validator = Validator::make($request->all(), $rules);        
        
        if($validator->fails()){
            return response()->json([
            'errors' => $validator->messages(),
            ]);
        }else{
            $user=new User();
            $user->email=$request->email;
            $user->name=ucwords($request->name);
            $user->password=bcrypt($request->password);
            // $user->is_admin= is_null($request->is_admin) ? 0 : 1; 
            $user->save();

            //device token for notificaton
            $token = new DeviceToken();
            $token->user_id= $user->id;
            $token->token = $request->token;
            $token->save();

            $http = new Client;
            
            $client = DB::table('oauth_clients')
                        ->where('password_client', true)
                        ->first();

            $response = $http->post(url('oauth/token'), [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ],
            ]);
            // return response (array(['auth' => json_decode($response->getBody(), true), 'user' => $user]));    ---  array of objects
           
            Mail::to($user->email)->send(new WelcomeMail());
            return response(['auth' => json_decode((string) $response->getBody(), true),'user' => $user, 'deviceToken' => $token->token]);    //-- object of objects 
        } 
	}

	public function login(Request $request){
		        
        $rules = [
            'email'=>'required|email',
            'password'=>'required'
            ];

        $validator = Validator::make($request->all(), $rules);
       
        if ($validator->fails()) {
            return response()->json([
            'errors' => $validator->messages(),
            ]);
        }else{
            $user= User::where('email',$request->email)->first();
            if($user){
                if(Hash::check($request->password, $user->password)){
                    $http = new Client;
            
                    $client = DB::table('oauth_clients')
                            ->where('password_client', true)
                            ->first();

                    $response = $http->post(url('oauth/token'), [
                        'form_params' => [
                            'grant_type' => 'password',
                            'client_id' => $client->id,
                            'client_secret' => $client->secret,
                            'username' => $request->email,
                            'password' => $request->password,
                            'scope' => '',
                        ],
                    ]);
                    
                    //device token for notificaton
                    $userId = $user->id;
                    $dbToken = DeviceToken::where('user_id', $userId)->get('token');
                    $inputToken = $request->token;

                    if(!$dbToken->contains('token', $inputToken)){
                        $token = new DeviceToken();
                        $token->user_id= $user->id;
                        $token->token = $inputToken;
                        $token->save();
                    }

                    return response(['auth' => json_decode((string)$response->getBody(), true), 'user' => $user, 
                                                                                    'deviceToken' => empty($token) ? $inputToken  : $token->token]);
                }else{
                    return response()->json(['errors' => ['password'=>'Invalid Password / Password does not match']]);
                }
            }else{
                return response()->json(['errors' => ['email'=>'Email id does not exist!! Sign Up instead!!']]);
            }
        }

	}

	// public function refreshToken() {

    //     $accessToken = auth()->user()->token();
    //     $expiresAt = $accessToken->expires_at->format('Y-m-d');
    //     $currentDate = date("Y-m-d");
    //     // $date = date("Y-m-d\TH:i:s");   ///  date with timestamp as in mysql db
       
    //     if($currentDate > $expiresAt){              
	// 	    $http = new Client;

    //         $client = DB::table('oauth_clients')
    //                     ->where('password_client', true)
    //                     ->first();
       
    //         $response = $http->post(url('oauth/token'), [
    //             'form_params' => [
    //                 'grant_type' => 'refresh_token',
    //                 'refresh_token' => request('refresh_token'),
    //                 'client_id' => $client->id,
    //                 'client_secret' => $client->secret,
    //                 'scope' => '',
    //             ],
    //         ]);
    //         return json_decode((string) $response->getBody(), true);
    //     }else{
    //         return;
    //     }
	// }
    
    public function logout(Request $request)
        {
            $token = $request->token;       
            $accessToken = auth()->user()->token();
            // $accessToken = Auth::user()->token();

            DeviceToken::where('token', $token)->delete();

            $refreshToken = DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $accessToken->id)
                ->update([
                    'revoked' => true
                ]);

            $accessToken->revoke();

            return response()->json(['status' => "Succefully logged out"]);
        }

    public function sendOTP(Request $request)
    {
        $email = $request->email;
        $exists = User::where('email', $email)->exists();
        
        if($exists){
            
            $userId = User::where('email', $email)->first('id');
            Otp::where('user_id', $userId->id)->delete();

            $otp = mt_rand(10000,99999);
            $expiresAt = now()->addMinutes(10);

            $otpInsert = new Otp();
            $otpInsert->user_id = $userId->id;;
            $otpInsert->otp = $otp;
            $otpInsert->expires_at = $expiresAt;
            $otpInsert->save();
            
            Notification::route('mail', $email)->notify(new PasswordResetOTPNotification($otp));
            return response()->json("OTP Sent");
        }else{
            return response()->json(['errors' => "No account exists for this Email"]);
        }
        return response()->json("eroor");
    }

    public function verifyOtp(Request $request){
        $email = $request->email;
        $otp = $request->otp;

        $userId = User::where('email', $email)->first('id');
        $savedOtp = Otp::where('user_id', $userId->id)->first(['otp','expires_at']);
        
        $now = date("Y-m-d H:i:s"); 

        if($otp == $savedOtp->otp){
            if($now < $savedOtp->expires_at){
                return response()->json("OTP Verified");
            }else{
                return response()->json(['errors' => "OTP expired"]);
            }
        }else{
            return response()->json(['errors' => "Invalid OTP"]);
        }        
    }

    public function resetPassword(Request $request)
    {
        $email = $request->email;
        $password = $request->password;
        $otp = $request->otp;

        $userId = User::where('email', $email)->first('id');
        $savedOtp = Otp::where('user_id', $userId->id)->first(['otp','expires_at']);
        
        $now = date("Y-m-d H:i:s"); 

        if($otp == $savedOtp->otp){
            if($now < $savedOtp->expires_at){
                User::where('email', $email)->update(['password' => bcrypt($password)]);
                return response()->json("Password Reset. Enter new password to login");
            }else{
                return response()->json(['errors' => "OTP expired"]);
            }
        }else{
            return response()->json(['errors' => "Invalid OTP"]);
        }        
        
       
    }

    
}