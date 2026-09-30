<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/ping', function () {
    return 'pong';
});
Route::middleware(['role:pemilik'])->prefix('owner')->group(function () {
    Route::post('/motors', [OwnerController::class, 'storeMotor']);
    Route::get('/motors', [OwnerController::class, 'myMotors']);
    Route::get('/revenue', [OwnerController::class, 'revenueReport']);
});
Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::patch('/motors/{id}/verify', [AdminController::class, 'verifyMotor']);
    Route::patch('/bookings/{id}/confirm', [AdminController::class, 'confirmBooking']);
    Route::patch('/bookings/{id}/return', [AdminController::class, 'returnBooking']);
    Route::get('/reports/revenue', [AdminController::class, 'revenueReport']);
});
Route::get('/motors', [BookingController::class, 'availableMotors']);
Route::middleware(['role:penyewa'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::post('/payments', [BookingController::class, 'payBooking']);
    Route::get('/bookings/history', [BookingController::class, 'myBookings']);
});
