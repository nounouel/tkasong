<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\TransaksiMasukController;
use App\Http\Controllers\TransaksiKeluarController;
use App\Http\Controllers\UserController;                              
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FuzzyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenjualanAgregatController;
use App\Http\Controllers\KategoriController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::resource('barang', BarangController::class);
    Route::resource('transaksi-masuk', TransaksiMasukController::class);
    Route::resource('transaksi-keluar', TransaksiKeluarController::class);
    Route::get('penjualan-agregat/daily-sales/{id_barang}', [PenjualanAgregatController::class, 'getDailySales'])->name('penjualan-agregat.daily-sales');
    Route::resource('penjualan-agregat', PenjualanAgregatController::class)->only(['index']);
    Route::resource('user', UserController::class);
    Route::get('fuzzy/predict', [FuzzyController::class, 'predict'])->name('fuzzy.predict');
    Route::get('fuzzy/detail/{id}', [FuzzyController::class, 'detail'])->name('fuzzy.detail');
    Route::get('fuzzy/barang-detail/{id}', [FuzzyController::class, 'getBarangDetail'])->name('fuzzy.barang-detail');
    Route::post('fuzzy/update-status/{id}', [FuzzyController::class, 'updateStatus'])->name('fuzzy.update-status');
    Route::resource('kategori', KategoriController::class);
    Route::resource('fuzzy', FuzzyController::class);
});