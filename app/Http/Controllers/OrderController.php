<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Product;
use App\User;
use App\Models\DeviceToken;
use App\Models\Subscription;
use Razorpay\Api\Api;
use DB;
use App\Notifications\OrderDetailsNotification;
use Illuminate\Http\Request;

class OrderController extends Controller
{
   
    public function index()
    {
        $userId = auth('api')->user()->id;
        // $orders = Order::with('orderItems')->where('user_id', '=', $userId)->orderBy('id', 'desc')->get();
        $orders = Subscription::where('user_id', '=', $userId)->orderBy('id', 'desc')->get();
        
        return response()->json($orders);
    }

     // get purchased books to view
    public function getBooksOrdered()
    {
        $userId = auth('api')->user()->id;
        $orders = Order::with('orderItems:order_id,product_id')->where('user_id', '=', $userId)->orderBy('id', 'desc')->get('id');

        $flatten = $orders->pluck('orderItems.*.product_id')->flatten();
        $val = implode(',', $flatten->toArray());   // Reqd to keep Book query in order

        $books = Product::whereIn('id', $flatten)->orderByRaw(DB::raw("FIELD(id, $val)"))->get(['id', 'name','sample', 'book']);
         return response()->json($books);          
        // $books = Product::whereIn('id', $flatten)->get(['id', 'name','sample', 'book']); --- this query doesnt mainatain order due to whereIn  
        
        // foreach works only after flattening like below line else does not work-- 
        // $flatten = $orders->pluck('orderItems')->flatten();
        // foreach($flatten as $value){
        //    $data[] = $value->product_id;
        // }
        //  $d = implode(',', $data); // --- this is reqd to keep Book query result in order
        // $books = Product::whereIn('id', $data)->orderByRaw(DB::raw("FIELD(id, $d)"))->get(['id', 'name','sample', 'book']);
        // return response()->json($books);
    }

    public function create()
    {
        //
    }

    // //razorpay
    public function createOrder(Request $request)
    {
        $api_key = env('RAZORPAY_API_KEY');
        $api_secret = env('RAZORPAY_SECRET_KEY');
        
        $api = new Api($api_key, $api_secret);

        $userId = $request->userId;
        $receipt = "SD_Rec_".md5(uniqid($userId, true));
        // return response()->json($receipt);
        
        $totalPrice = $request->amount;
        $order  = $api->order->create([
                'receipt'         => $receipt,
                'amount'          => $totalPrice * 100,
                'currency'        => 'INR',
                'payment_capture' =>  '1'
            ]);

        // $order  = $api->order->create(array('receipt' => '123', 'amount' => 100, 'currency' => 'INR')); // Creates order
        $orderId = $order['id']; // Get the created Order ID
        $order  = $api->order->fetch($orderId);
        // $orders = $api->order->all($options); // Returns array of order objects
        // $payments = $api->order->fetch($orderId)->payments(); // Returns array of payment objects against an order
        // return $order;
        return response()->json(['rzPayOrderId' => $orderId, 'receipt' => $receipt, 'api_key' =>$api_key]);
    }   

    // save to orders & orderItems -- COD
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
            $order = new Order();
            $order->user_id  = $request->userId;
            $order->total_price  = $request->totalPrice;
            $order->payment_id  = $request->paymentId;
            $order->receipt  = $request->receipt;
            $order->save();
        
            $cart = $request->cart;
            for($i=0; $i<count($cart); $i++){
                $orderItems = new OrderItems();        
                $orderItems->order_id = $order->id;
                $orderItems->product_id = $cart[$i]['id'];
                $orderItems->product_name = $cart[$i]['name'];
                $orderItems->price = $cart[$i]['price'] * $cart[$i]['quantity'];
                $orderItems->save();  
            }
            
            auth('api')->user()->notify(new OrderDetailsNotification($order));
            return response()->json(['msg'=>'Insert Successful!']);
        }
    }
    //generate random value (andom-unique-alphanumeric-string) for recpt
    // public function test(){
    //       $receipt = "Rec".md5(uniqid(rand(), true));
    //       $val = md5(uniqid(1, true));
    //       return response()->json($receipt);
    // }



    public function fcm(Request $request){
        
        // define('API_ACCESS_KEY', env('FCM_SERVER_KEY'));
        $fcmServerKey = env('FCM_SERVER_KEY');

        $users = User::where('is_admin', 1)->get();
        $userIds = $users->pluck('id');
        
        $token = DeviceToken::whereIn('user_id', $userIds)->get('token');
        $allTokens = $token->pluck('token');
        
        
        $title = $request->title;
        $message = $request->message;
        // $type = $request->type;

        // $notification_ids = $request->notification_ids;
        // $registrationIds = array($notification_ids);
       
        $registrationIds = $allTokens;
       

        $msg = array
            ( 
                'title'         => $title,
                'message'       => $message,
                'click_action'  => 'FCM_PLUGIN_ACTIVITY',
                'vibrate'       => 1,
                'sound'         => 1,
                // 'type'          => $type
            );
     
        $fields = array
        (
            'registration_ids'  => $registrationIds,
            'data'              => $msg,
            'priority' => 'high',
            'notification' => array(
                'title' => $title,
                'body' => $message,
            ),
            // 'data'=> array(
            //     'name'=>'rishi'
            // )
           
        );
         
        $headers = array
        (
            'Authorization: key=' . $fcmServerKey,
            'Content-Type: application/json'
        );
         
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        // return response()->json($result);
        return $result;
}

    public function show(Order $order)
    {
        //
    }

    public function edit(Order $order)
    {
        //
    }

    public function update(Request $request, Order $order)
    {
        //
    }

    public function destroy(Order $order)
    {
        //
    }
}