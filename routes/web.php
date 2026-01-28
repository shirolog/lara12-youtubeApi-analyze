<?php

use App\Http\Controllers\YoutubeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/youtube', [YoutubeController::class, 'index'])->name('youtube.index');
Route::post('/youtube', [YoutubeController::class, 'analyze'])->name('youtube.analyze');