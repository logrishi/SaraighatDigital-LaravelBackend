<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
   
    public function __construct()
    {
        // $this->middleware('auth:api')->except(['index']);
        $this->middleware('isAdmin')->except(['index','getFreeProducts','getVersion']);
    }

    public function getVersion(){
        $version = 2;
        return response()->json($version); 
    }


    public function index()
    {
        // $products = Product::where('for_sale', 1)->get();
        // $products = Product::all();
        // $products = Product::orderBy('id','desc')->get();
        $products = Product::where('for_sale', 1)
                            ->where('is_free', 0)
                            ->orderBy('id','desc')
                            ->paginate(10);
        return response()->json($products);
    }

    public function getFreeProducts()
    {
        $products = Product::where('is_free', 1)->orderBy('id','desc')->get();
        return response()->json($products);
    }

    public function getAllProducts()
    {
        $products = Product::orderBy('id','desc')->get();
        return response()->json($products);
    }


    public function create()
    {
       //
    }

    public function store(Request $request)
    {
        // Validations
        // $request->validate([
        //     'name'          => 'required|unique:products',
        //     'description'   => 'required',
        //     'price'         => 'required|numeric',
        //     'sample'        => 'required',
        //     'book'          => 'required',
        //     'is_free'       => 'required'
        // ]);
        
        // return $request; 

            $ext = $request->file('sample')->getClientOriginalExtension();
            $exts = $request->file('book')->getClientOriginalExtension();
                
            $productName = $request->name;
            $product = new Product();
            $product->name = ucwords($productName);
            $product->description = $request->description; 
            $product->price = $request->price; 
            $product->sample = $request->sample->storeAs('samples', date('mdYHis').random_int(100, 999).'.'.$ext, 'public');
            $product->book = $request->book->storeAs('books', date('mdYHis').random_int(100, 999).'.'.$exts,'public');                
            // $product->for_sale = is_null($request->for_sale) ? 1 : 0; 
            $product->is_free = $request->is_free;
        
            $product->save();
            return response()->json('Insert Successful!');         
        
    }

    public function updateProducts(Request $request)
    {                    
            $product = Product::find($request->id); 
            $productId = $request->id;
           
            $name = $request->name;
            $productName = ucwords($name);
            $description = $request->description; 
            $price = $request->price; 
            $for_sale = $request->for_sale; 
            
            if($for_sale == "False"){
                $sale = 0;
            }elseif($for_sale == "True"){
                $sale = 1;
            }elseif($for_sale == 0){
                $sale = 0;
            }elseif($for_sale == 1){
                $sale = 1;
            }

            $is_free = $request->is_free; 
            
            if($is_free == "False"){
                $free = 0;
            }elseif($is_free == "True"){
                $free = 1;
            }elseif($is_free == 0){
                $free = 0;
            }elseif($is_free == 1){
                $free = 1;
            }
            
            if($request->sample != 'null'){
                $ext = $request->file('sample')->getClientOriginalExtension();
                $sample = $request->sample->storeAs('samples', date('mdYHis').random_int(100, 999).'.'.$ext, 'public');
            }
            else{
                $sample = $product->sample;
            }
            if($request->book != 'null'){
                $exts = $request->file('book')->getClientOriginalExtension();
                $book = $request->book->storeAs('books', date('mdYHis').random_int(100, 999).'.'.$exts,'public');   
            }
            else{
                $book = $product->book;
            }
            // return response()->json($book);
            Product::where('id', $productId)->update(['name' => $productName, 'description' => $description, 'price' => $price, 
                                                        'for_sale' => $sale, 'is_free' => $free, 
                                                        'sample' => $sample, 'book' => $book]);

            return response()->json('Update Successful!');
    }

    public function deleteProducts(Request $request)
    {   
         $product = Product::find($request->id); 
         $productId = $request->id;

         Storage::delete('public/'.$product->sample);
         Storage::delete('public/'.$product->book);
         Product::where('id', $productId)->delete();
         Cart::where('product_id', $productId)->delete();
          
         return response()->json("Successfully deleted");

    }

    public function show(Product $product)
    {
        //
    }

    public function edit(Product $product)
    {
        //
    }

    public function update(Request $request)
    {       
        //
    }

    public function destroy(Product $product)
    {
        //
    }
}