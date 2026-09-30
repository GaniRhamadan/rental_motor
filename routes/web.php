<?php

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/rent/dashboard');
});

Route::get('/admin/dashboard', [WebController::class, 'adminDashboard']);
Route::get('/owner/dashboard', [WebController::class, 'ownerDashboard']);
Route::get('/rent/dashboard', [WebController::class, 'rentDashboard']);
Route::post('/owner/motors/web', [WebController::class, 'storeMotorWeb']);
Route::post('/admin/motors/{id}/verify-web', [WebController::class, 'verifyMotorWeb']);
Route::post('/rent/motors/{id}/book-web', [WebController::class, 'bookMotorWeb']);
Route::post('/admin/bookings/{id}/confirm-web', [WebController::class, 'confirmBookingWeb']);
Route::post('/admin/bookings/{id}/return-web', [WebController::class, 'returnBookingWeb']);
Route::get('/login', [WebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebController::class, 'loginWeb']);
Route::get('/register', [WebController::class, 'showRegisterForm']);
Route::post('/register', [WebController::class, 'registerWeb']);
Route::post('/logout', [WebController::class, 'logoutWeb']);
Route::post('/owner/motors/{id}/update-web', [WebController::class, 'updateMotorWeb']);
Route::post('/owner/motors/{id}/delete-web', [WebController::class, 'deleteMotorWeb']);
