<?php

use App\Http\Controllers\Ai\AiController;

Route::post('/send', [AiController::class, 'sendAudio'])->name('send-audio');
Route::get('/voice1', [AiController::class, 'parseVoice'])->name('parse.voice');
Route::get('/romanian', [AiController::class, 'romanian'])->name('answer');
Route::post('/detail', [AiController::class, 'detail'])->name('detail');
