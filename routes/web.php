<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//Base routes
Route::inertia('/','Home')->name('home');
Route::inertia('/about', 'About', ['user' => 'Kolya'])->name('about');

//Auth routes
Route::inertia('/login', 'Auth/Login')->name('login');
Route::inertia('/register', 'Auth/Register')->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
