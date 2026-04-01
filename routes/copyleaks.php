<?php

use hexa_package_copyleaks\Http\Controllers\CopyleaksController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('copyleaks/settings', [CopyleaksController::class, 'settings'])->name('copyleaks.settings');
    Route::post('copyleaks/settings', [CopyleaksController::class, 'saveSettings'])->name('copyleaks.settings.save');
    Route::post('copyleaks/test', [CopyleaksController::class, 'testConnection'])->name('copyleaks.test');
    Route::get('raw-copyleaks', [CopyleaksController::class, 'raw'])->name('copyleaks.raw');
    Route::post('copyleaks/detect', [CopyleaksController::class, 'detect'])->name('copyleaks.detect');
});
