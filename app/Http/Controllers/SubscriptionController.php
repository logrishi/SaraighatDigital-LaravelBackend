<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Subscription;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Razorpay\Api\Api;
use App\Notifications\OrderDetailsNotification;
use Carbon\Carbon;
use PDO;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = auth('api')->user()->id;
        $subscriptions = Subscription::where('user_id', $userId)->get();

        if(count($subscriptions) > 0){
            foreach($subscriptions as $subs){
                $expiresOn = $subs['expires_on'];
                $expiresOn = date('Y-m-d', strtotime($expiresOn));
                $today = date('Y-m-d');
            
                if($today <= $expiresOn){
                    $books = Product::where('is_free', 0)->orderBy('id','desc')->get(['id', 'name','sample', 'book']);
                    // $books = Product::where('is_free', 0)->paginate(25,['id', 'name','sample', 'book']);
                    return response()->json([$books, $expiresOn]);
                }
            }
        }
        // return response()->json([]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //verify payment signature
        $razorpaySignature = $request->razorpay_signature;
        $razorpayPaymentId = $request->rzPayPaymentId;
        $razorpay_order_id = $request->rzPayOrderId;
        // return [$razorpaySignature,$razorpayPaymentId,$razorpay_order_id];
        
        $api_secret = env('RAZORPAY_SECRET_KEY');
        $api_key = env('RAZORPAY_API_KEY');
        
        $api = new Api($api_key, $api_secret);
        $attributes = [
            'razorpay_signature' => $razorpaySignature,
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_order_id' => $razorpay_order_id,
        ];
        
        $verified = false;
        try {
            $api->utility->verifyPaymentSignature($attributes);
            $verified = true;
        } catch (SignatureVerificationError $e) {
            $verified = false;
        }
        
        if ($verified) {

            $expiresOn = date('Y-m-d', strtotime('+1 year'));

            $subscription = new Subscription();
            $subscription->user_id  = $request->userId;
            $subscription->price  = $request->price;
            $subscription->payment_id  = $request->paymentId;
            $subscription->receipt  = $request->receipt;
            $subscription->expires_on  = $expiresOn;
            $subscription->save();
            
            auth('api')->user()->notify(new OrderDetailsNotification($subscription));
            return response()->json(['msg'=>'Insert Successful!']);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function show(Subscription $subscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subscription $subscription)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}