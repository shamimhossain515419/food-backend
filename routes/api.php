<?php

use App\Http\Controllers\OrdersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    //user info
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('dashboard', [AuthController::class, 'dashboard']);
    Route::get('get-all-customer', [AuthController::class, 'getAllCustomer']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('profile', [AuthController::class, 'profile'])->middleware(['auth:api', 'role_and_block_status:false,1,2,3']);  // Check that the 'role_and_block_status' key matches the Kernel registration

});

//deposit prefix
Route::group([
    'middleware' => 'api',
    'prefix' => 'product',
], function ($router) {
    Route::apiResource('product-category', CategoryController::class);
    Route::post('update-category/{id}', [CategoryController::class, 'updateCategory']);
    Route::apiResource('products', ProductController::class);
    Route::post('update-product/{id}', [ProductController::class, 'updateProduct']);
    Route::get('category-wise-product', [ProductController::class, 'getCategoryWiseProduct']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'orders',
], function ($router) {
    Route::apiResource('orders', controller: OrdersController::class);
    Route::get('get-single-user-order', [OrdersController::class, 'getSingleUserAllOrder']);

});


Route::group([
    'middleware' => 'api',
    'prefix' => 'review',
], function ($router) {
    Route::apiResource('review', controller: ReviewController::class);

});