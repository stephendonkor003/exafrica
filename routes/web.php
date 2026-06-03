<?php

use App\Http\Controllers\BackOfficeWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('back-office')->name('backoffice.')->group(function () {
    Route::get('/login', [BackOfficeWebController::class, 'loginForm'])->name('login');
    Route::post('/login', [BackOfficeWebController::class, 'login'])->name('login.submit');
    Route::get('/', [BackOfficeWebController::class, 'dashboard'])->name('dashboard');
    Route::get('/nominations/{nomination}', [BackOfficeWebController::class, 'showNomination'])->name('nominations.show');
    Route::post('/logout', [BackOfficeWebController::class, 'logout'])->name('logout');
});
