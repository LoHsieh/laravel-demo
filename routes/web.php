<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/beans', [ProductController::class, 'index'])->name('products.index');
Route::get('/beans/{product}', [ProductController::class, 'show'])->name('products.show');
