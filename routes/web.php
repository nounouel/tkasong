<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\TraningController;                              
use App\Http\Controllers\UserController;                              

Route::get('/', function () {
    return view('welcome');
});
Route::get('/home', function () {
    return view('dashboard.home');
})->name('home');
Route::resource('barang', BarangController::class);
Route::resource('traning', TraningController::class);
Route::resource('user', UserController::class);