<?php

namespace App\Http\Controllers;

use App\Models\GeoAnalysis;
use App\Models\Lead;
use App\Models\TrendAnalysis;
use Illuminate\Http\Response;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class AnalysisDocxController extends Controller
{
    private const LEAD_ANALYSIS_TYPES = ['prospect', 'website', 'trend', 'geo'];

    private const COMPANY_ANALYSIS_TYPES = ['trend', 'geo'];

    private const COLOR_PRIMARY = '1e5a96';

    private const COLOR_HEADING = '374151';

    private const COLOR_META = '9ca3af';

    public function downloadLeadAnalysis(Lead $lead, string $type): Response
    {
        abort_unless(in_array($type, self::LEAD_ANALYSIS_TYPES, true), 404);

        $analysis = $lead->{$type.'Analysis'};

        abort_if(! $analysis || $analysis->status !== 'completed', 404);

        $phpWord = match ($type) {
            'prospect' => $this->buildProspectDoc($lead, $analysis),
            'website' => $this->buildWebsiteDoc($lead, $analysis),
            'trend' => $this->buildTrendDoc($lead, $analysis),
            'geo' => $this->buildGeoDoc($lead, $analysis),
        };

        $filename = str($lead->title)->slug()->append('-'.$type.'-analysis.docx')->toString();

        return $this->streamDocx($phpWord, $filename);
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

        $phpWord = match ($type) {
            'trend' => $this->buildTrendDoc(null, $analysis),
            'geo' => $this->buildGeoDoc(null, $analysis),
        };

        $subject = $type === 'trend'
            ? str($analysis->topic)->slug()->toString()
            : str($analysis->url)->slug()->toString();

        return $this->streamDocx($phpWord, $subject.'-'.$type.'-analysis.docx');
    }

    private function makePhpWord(): PhpWord
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->addTitleStyle(1, ['name' => 'Calibri', 'size' => 20, 'bold' => true, 'color' => self::COLOR_PRIMARY]);
        $phpWord->addTitleStyle(2, ['name' => 'Calibri', 'size' => 13, 'bold' => true, 'color' => self::COLOR_HEADING]);

        return $phpWord;
    }

    private function addTextSection(Section $section, string $heading, ?string $text): void
    {
        if (empty($text)) {
            return;
        }

        $section->addTitle($heading, 2);
        $section->addText($text);
        $section->addTextBreak(1);
    }

    private function addListSection(Section $section, string $heading, array $items): void
    {
        $items = array_filter(array_map('strval', $items));

        if (empty($items)) {
            return;
        }

        $section->addTitle($heading, 2);

        foreach ($items as $item) {
            $section->addListItem($item, 0);
        }

        $section->addTextBreak(1);
    }

    private function addMeta(Section $section, mixed $analysis): void
    {
        $section->addText(
            'Analysed '.($analysis->completed_at?->format('d M Y') ?? '–').' · '.($analysis->model ?? ''),
            ['size' => 9, 'color' => self::COLOR_META],
        );
    }

    private function buildProspectDoc(?Lead $lead, mixed $analysis): PhpWord
    {
        $phpWord = $this->makePhpWord();
        $section = $phpWord->addSection();
        $result = $analysis->result ?? [];

        $section->addTitle(($lead ? $lead->title.' – ' : '').'Prospect Analysis', 1);

        if ($lead?->website) {
            $section->addText($lead->website, ['color' => self::COLOR_META]);
        }

        if (isset($result['prospect_score'])) {
            $section->addText('Prospect Score: '.(int) $result['prospect_score'].'/100', ['bold' => true, 'size' => 12, 'color' => self::COLOR_PRIMARY]);
        }

        $section->addTextBreak(1);

        $this->addTextSection($section, 'Company Fit', $result['company_fit'] ?? null);
        $this->addTextSection($section, 'Contact Intelligence', $result['contact_intel'] ?? null);
        $this->addTextSection($section, 'Opportunity', $result['opportunity'] ?? null);
        $this->addTextSection($section, 'Competitive Intelligence', $result['competitive_intel'] ?? null);
        $this->addTextSection($section, 'Outreach Strategy', $result['outreach_strategy'] ?? null);

        $this->addMeta($section, $analysis);

        return $phpWord;
    }

    private function buildWebsiteDoc(?Lead $lead, mixed $analysis): PhpWord
    {
        $phpWord = $this->makePhpWord();
        $section = $phpWord->addSection();
        $result = $analysis->result ?? [];
        $techStack = (array) ($analysis->scraped_data['tech_stack'] ?? []);

        $section->addTitle(($lead ? $lead->title.' – ' : '').'Website Analysis', 1);

        if ($lead?->website) {
            $section->addText($lead->website, ['color' => self::COLOR_META]);
        }

        if (isset($result['overall_score'])) {
            $section->addText('Overall Score: '.(int) $result['overall_score'].'/100', ['bold' => true, 'size' => 12, 'color' => self::COLOR_PRIMARY]);
        }

        $section->addTextBreak(1);

        $this->addTextSection($section, 'Business Overview', $result['business_overview'] ?? null);
        $this->addTextSection($section, 'Value Proposition', $result['value_proposition'] ?? null);
        $this->addListSection($section, 'Sales Angles', (array) ($result['sales_angles'] ?? []));
        $this->addListSection($section, 'Pain Points', (array) ($result['pain_points'] ?? []));
        $this->addTextSection($section, 'Competitive Position', $result['competitive_position'] ?? null);
        $this->addTextSection($section, 'Growth Signals', $result['growth_signals'] ?? null);
        $this->addListSection($section, 'Tech Stack', $techStack);

        $this->addMeta($section, $analysis);

        return $phpWord;
    }

    private function buildTrendDoc(?Lead $lead, mixed $analysis): PhpWord
    {
        $phpWord = $this->makePhpWord();
        $section = $phpWord->addSection();
        $result = $analysis->result ?? [];
        $topic = $analysis->topic ?? null;

        $title = $topic ? $topic.' – Trend Analysis' : ($lead ? $lead->title.' – Trend Analysis' : 'Trend Analysis');
        $section->addTitle($title, 1);

        if ($lead?->website) {
            $section->addText($lead->website, ['color' => self::COLOR_META]);
        }

        if (isset($result['relevance_score'])) {
            $section->addText('Relevance Score: '.(int) $result['relevance_score'].'/100', ['bold' => true, 'size' => 12, 'color' => self::COLOR_PRIMARY]);
        }

        $section->addTextBreak(1);

        if ($topic) {
            $this->addTextSection($section, 'Research Topic', $topic);
        }

        $this->addTextSection($section, 'Market Overview', $result['market_overview'] ?? null);
        $this->addListSection($section, 'Opportunities', (array) ($result['opportunities'] ?? []));
        $this->addListSection($section, 'Talking Points', (array) ($result['talking_points'] ?? []));
        $this->addListSection($section, 'Trending Topics', (array) ($result['trending_topics'] ?? []));
        $this->addTextSection($section, 'Community Sentiment', $result['community_sentiment'] ?? null);

        $this->addMeta($section, $analysis);

        return $phpWord;
    }

    private function buildGeoDoc(?Lead $lead, mixed $analysis): PhpWord
    {
        $phpWord = $this->makePhpWord();
        $section = $phpWord->addSection();
        $result = $analysis->result ?? [];
        $url = $analysis->url ?? $lead?->website;

        $title = $url ? $url.' – GEO Analysis' : ($lead ? $lead->title.' – GEO Analysis' : 'GEO Analysis');
        $section->addTitle($title, 1);

        if ($url) {
            $section->addText($url, ['color' => self::COLOR_META]);
        }

        if (isset($result['geo_score'])) {
            $section->addText('GEO Score: '.(int) $result['geo_score'].'/100', ['bold' => true, 'size' => 12, 'color' => self::COLOR_PRIMARY]);
        }

        $section->addTextBreak(1);

        $this->addTextSection($section, 'AI Visibility Summary', $result['ai_visibility_summary'] ?? null);
        $this->addListSection($section, 'Sales Angles', (array) ($result['sales_angles'] ?? []));
        $this->addListSection($section, 'Quick Wins', (array) ($result['quick_wins'] ?? []));
        $this->addTextSection($section, 'Citability Assessment', $result['citability_assessment'] ?? null);
        $this->addTextSection($section, 'Brand Authority', $result['brand_authority_assessment'] ?? null);
        $this->addTextSection($section, 'Schema Markup', $result['schema_assessment'] ?? null);
        $this->addTextSection($section, 'Technical SEO', $result['technical_assessment'] ?? null);
        $this->addListSection($section, 'Platform Recommendations', (array) ($result['platform_recommendations'] ?? []));

        $this->addMeta($section, $analysis);

        return $phpWord;
    }

    private function streamDocx(PhpWord $phpWord, string $filename): Response
    {
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean() ?: '';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
