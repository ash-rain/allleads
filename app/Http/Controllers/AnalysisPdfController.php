<?php

namespace App\Http\Controllers;

use App\Models\GeoAnalysis;
use App\Models\Lead;
use App\Models\TrendAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AnalysisPdfController extends Controller
{
    private const LEAD_ANALYSIS_TYPES = ['prospect', 'website', 'trend', 'geo'];

    private const COMPANY_ANALYSIS_TYPES = ['trend', 'geo'];

    public function downloadLeadAnalysis(Lead $lead, string $type): Response
    {
        abort_unless(in_array($type, self::LEAD_ANALYSIS_TYPES, true), 404);

        $relationship = $type.'Analysis';
        $analysis = $lead->{$relationship};

        abort_if(! $analysis || $analysis->status !== 'completed', 404);

        $pdf = Pdf::loadView("pdf.{$type}-analysis", [
            'lead' => $lead,
            'analysis' => $analysis,
        ]);

        $filename = str($lead->title)->slug()->append('-'.$type.'-analysis.pdf')->toString();

        return $pdf->download($filename);
    }

    public function downloadCompanyAnalysis(string $type, int $id): Response
    {
        abort_unless(in_array($type, self::COMPANY_ANALYSIS_TYPES, true), 404);

        $model = match ($type) {
            'trend' => TrendAnalysis::class,
            'geo' => GeoAnalysis::class,
        };

        $analysis = $model::where('user_id', auth()->id())->findOrFail($id);

        abort_if($analysis->status !== 'completed', 404);

        $pdf = Pdf::loadView("pdf.{$type}-analysis", [
            'lead' => null,
            'analysis' => $analysis,
        ]);

        $subject = $type === 'trend'
            ? str($analysis->topic)->slug()->toString()
            : str($analysis->url)->slug()->toString();

        $filename = $subject.'-'.$type.'-analysis.pdf';

        return $pdf->download($filename);
    }
}
