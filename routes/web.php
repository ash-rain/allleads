<?php

use App\Http\Controllers\AnalysisDocxController;
use App\Http\Controllers\AnalysisPdfController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::middleware([Authenticate::class])->group(function (): void {
    Route::get('/app/intelligence/{lead}/{type}/pdf', [AnalysisPdfController::class, 'downloadLeadAnalysis'])
        ->name('intelligence.analysis.pdf');

    Route::get('/app/business-intelligence/{type}/{id}/pdf', [AnalysisPdfController::class, 'downloadCompanyAnalysis'])
        ->name('business-intelligence.analysis.pdf');

    Route::get('/app/intelligence/{lead}/{type}/docx', [AnalysisDocxController::class, 'downloadLeadAnalysis'])
        ->name('intelligence.analysis.docx');

    Route::get('/app/business-intelligence/{type}/{id}/docx', [AnalysisDocxController::class, 'downloadCompanyAnalysis'])
        ->name('business-intelligence.analysis.docx');
});
