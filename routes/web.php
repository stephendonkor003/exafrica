<?php

use App\Http\Controllers\BackOfficeWebController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [PublicPageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicPageController::class, 'robots'])->name('robots');

Route::prefix('back-office')->name('backoffice.')->group(function () {
    Route::get('/login', [BackOfficeWebController::class, 'loginForm'])->name('login');
    Route::post('/login', [BackOfficeWebController::class, 'login'])
        ->middleware('throttle:backoffice-login')
        ->name('login.submit');
    Route::get('/', [BackOfficeWebController::class, 'dashboard'])->name('dashboard');
    Route::get('/nominations/{nomination}', [BackOfficeWebController::class, 'showNomination'])->name('nominations.show');
    Route::get('/nominations/{nomination}/documents/{document}', [BackOfficeWebController::class, 'downloadNominationDocument'])
        ->whereNumber('document')
        ->name('nominations.documents.show');
    Route::post('/logout', [BackOfficeWebController::class, 'logout'])->name('logout');
});
