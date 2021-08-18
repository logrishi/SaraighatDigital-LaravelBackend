<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//auth
Route::post('signup', 'API\AuthController@register');
Route::post('login', 'API\AuthController@login');
Route::middleware('auth:api')->post('logout', 'API\AuthController@logout');

Route::post('sendOTP', 'API\AuthController@sendOTP');
Route::post('verifyOtp', 'API\AuthController@verifyOtp');
Route::post('resetPassword', 'API\AuthController@resetPassword');

// Route::post('refreshToken', 'API\AuthController@refreshToken');

//admin - manage orders
Route::middleware('isAdmin')->resource('manageOrders', 'Admin\ManageOrdersController');
Route::middleware('isAdmin')->get('subscriptionOrders', 'Admin\ManageOrdersController@subscriptionOrders');

//products
Route::resource('products', 'Admin\ProductController');
Route::middleware('auth:api')->get('getFreeProducts', 'Admin\ProductController@getFreeProducts');
Route::get('getVersion', 'Admin\ProductController@getVersion');
Route::get('getAllProducts', 'Admin\ProductController@getAllProducts');
Route::post('updateProducts', 'Admin\ProductController@updateProducts');
Route::post('deleteProducts', 'Admin\ProductController@deleteProducts');

//orders, payments, notification
Route::middleware('auth:api')->post('createOrder', 'OrderController@createOrder');
Route::middleware('auth:api')->resource('payments', 'PaymentController');
Route::middleware('auth:api')->resource('orders', 'OrderController');
Route::middleware('auth:api')->get('getBooksOrdered', 'OrderController@getBooksOrdered');
Route::middleware('auth:api')->post('fcm', 'OrderController@fcm');

//subscription
Route::middleware('auth:api')->resource('subscriptions', 'SubscriptionController');

//cart
Route::middleware('auth:api')->resource('cart', 'CartController');
Route::middleware('auth:api')->post('cartVerify', 'CartController@cartVerify');