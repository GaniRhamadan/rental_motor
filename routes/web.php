<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;

Route::get('/', function () {
    return redirect('/admin/dashboard');
});

Route::get('/admin/dashboard', [WebController::class, 'adminDashboard']);
Route::get('/owner/dashboard', [webController::class, 'ownerDashboard']);
Route::get('/rent/dashboard', [webController::class, 'rentDashboard']);
Route::post('/owner/motors/web', [webController::class, 'storeMotorWeb']);
Route::post('/admin/motors/{id}/verify-web', [webController::class, 'verifyMotorWeb']);
Route::post('/rent/motors/{id}/book-web', [webController::class, 'bookMotorWeb']);
Route::post('/admin/bookings/{id}/confirm-web', [WebController::class, 'confirmBookingWeb']);
Route::post('/admin/bookings/{id}/return-web    ', [WebController::class, 'returnBookingWeb']);
