<?php

use App\Http\Controllers\Ai\AiController;

Route::post('/send', [AiController::class, 'sendAudio'])->name('send-audio');
Route::get('/voice', [AiController::class, 'parseVoice'])->name('parse.voice');
