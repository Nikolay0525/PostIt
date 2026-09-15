<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');


Route::inertia('/about', 'About', ['user' => 'Kolya'])->name('about');
