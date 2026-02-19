<?php

use App\Http\Controllers\AttachmentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}', AttachmentDownloadController::class)
        ->name('attachments.download');
});
