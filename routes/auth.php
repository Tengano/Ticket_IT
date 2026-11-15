<?php

use App\Http\Controllers\Auth\DangKyController;
use App\Http\Controllers\Auth\DatLaiMatKhauController;
use App\Http\Controllers\Auth\DoiMatKhauController;
use App\Http\Controllers\Auth\GuiLienKetDatLaiMatKhauController;
use App\Http\Controllers\Auth\GuiXacThucEmailController;
use App\Http\Controllers\Auth\NhacXacThucEmailController;
use App\Http\Controllers\Auth\PhienDangNhapController;
use App\Http\Controllers\Auth\XacNhanMatKhauController;
use App\Http\Controllers\Auth\XacThucEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [DangKyController::class, 'create'])
        ->name('register');

    Route::post('register', [DangKyController::class, 'store']);

    Route::get('login', [PhienDangNhapController::class, 'create'])
        ->name('login');

    Route::post('login', [PhienDangNhapController::class, 'store']);

    Route::get('forgot-password', [GuiLienKetDatLaiMatKhauController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [GuiLienKetDatLaiMatKhauController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [DatLaiMatKhauController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [DatLaiMatKhauController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', NhacXacThucEmailController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', XacThucEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [GuiXacThucEmailController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [XacNhanMatKhauController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [XacNhanMatKhauController::class, 'store']);

    Route::put('password', [DoiMatKhauController::class, 'update'])->name('password.update');

    Route::post('logout', [PhienDangNhapController::class, 'destroy'])
        ->name('logout');
});
