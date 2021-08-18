<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Http\Request;

class ManageOrdersController extends Controller
{
    
    public function index()
    {
        $orders = Order::with('orderItems', 'payment', 'user')->orderBy('id', 'desc')->get();
        // $orders = Order::with('orderItems', 'payment', 'address')->get()->groupBy('orderItems.order_id');
        // $orders = Order::with('orderItems', 'payment', 'address')->get()->unique('orderItems.order_id');
        return response()->json($orders);
        // for($i = 0; $i < count($orders); $i++){
        //     $payments[] = Payment::where('id', '=', $orders[$i]->payment_id)->get();
        //     $address[] = Address::where('id', '=', $orders[$i]->address_id)->get();
        // } 
        // return response()->json(['orders' => $orders, 'payments' => $payments, 'address' => $address]);
    }

    public function subscriptionOrders()
    {
        $orders = Subscription::with('payment', 'user')->orderBy('id', 'desc')->get();
        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request)
    {
        $orderId = $request->order_id;
        $orderStatus = $request->order_status;
        $orderStatusCode = $request->order_status_code;
        
        Order::where('id', $orderId)->update(['order_status' => $orderStatus, 'order_status_code' => $orderStatusCode]);
        return response()->json("Order Status Updated");
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}