<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/beans', [ProductController::class, 'index'])->name('products.index');
Route::get('/beans/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{key}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{key}', [CartController::class, 'destroy'])->name('cart.items.destroy');
