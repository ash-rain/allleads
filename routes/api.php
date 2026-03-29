<?php

use App\Http\Controllers\Webhooks\BrevoEventsController;
use App\Http\Controllers\Webhooks\BrevoInboundController;
use App\Http\Middleware\VerifyBrevoWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks/brevo')
    ->middleware(VerifyBrevoWebhookSignature::class)
    ->group(function (): void {
        Route::post('/inbound', BrevoInboundController::class)->name('webhooks.brevo.inbound');
        Route::post('/events', BrevoEventsController::class)->name('webhooks.brevo.events');
    });
