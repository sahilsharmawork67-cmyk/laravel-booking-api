<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');

Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
