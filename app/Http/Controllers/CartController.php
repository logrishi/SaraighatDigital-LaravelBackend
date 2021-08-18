<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use DB;

class CartController extends Controller
{

    public function index()
    {
        $userId = auth('api')->user()->id;
        $cart  = Cart::where('user_id', $userId)->get(['product_id','quantity','cart_qty']);

        foreach($cart as $c){
            $productIds[] = $c['product_id'];
            $cartQty = $c['cart_qty'];
        }
         $val = implode(',', $productIds); 
        // $products = Product::whereIn('name', $books)->where('for_sale', 1)->orderByRaw(DB::raw("FIELD(id, $books)"))->get('name');
        $products = Product::whereIn('id', $productIds)->orderByRaw(DB::raw("FIELD(id, $val)"))->get();

        return response()->json(['products' => $products, 'cartQty' => $cartQty]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $userId= $request->userId;
        $data = $request->cart;
        $cartQty = $request->cartQty;

        // $d = json_encode($cart, true);          //without this works in postman but not in RN
        // $cartArr = json_decode($d, true); 
        // var_dump($cartArr);

        Cart::where('user_id', $userId)->delete();
        
        for($i=0; $i<count($data); $i++){
            $items = new Cart();   
            $items->user_id = $userId;     
            $items->cart_qty = $cartQty;     
            $items->product_id = $data[$i]['id'];
            $items->quantity = $data[$i]['quantity'];
            $items->save();  
         }
        return response()->json($data);
    }

    public function cartVerify(Request $request)
    {
        $data = $request->cart;

        // without below lines result in RN  but error in postman
        // $d = json_encode($cart, true);          //without this works in postman but not in RN
        // $cartArr = json_decode($d, true);       //using both of these works in RN not in Postman

        foreach($data as $c){
            $productIds[]= $c['id'];
        }
        $val = implode(',', $productIds); 
        // $products = Product::whereIn('name', $books)->where('for_sale', 1)->orderByRaw(DB::raw("FIELD(id, $books)"))->get('name');
        $products = Product::whereIn('id', $productIds)->orderByRaw(DB::raw("FIELD(id, $val)"))->get();
        return response()->json($products);
    }

    public function show(Cart $cart)
    {
        //
    }

    public function edit(Cart $cart)
    {
        //
    }

    public function update(Request $request, Cart $cart)
    {
        //
    }

    public function destroy(Cart $cart)
    {
        //
    }
}