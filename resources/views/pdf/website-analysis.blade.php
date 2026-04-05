@php
    $result = $analysis->result ?? [];
    $score = isset($result['overall_score']) ? (int) $result['overall_score'] : null;
    $scoreClass = $score === null ? '' : ($score >= 75 ? 'score-green' : ($score >= 50 ? 'score-yellow' : 'score-red'));
    $techStack = $analysis->scraped_data['tech_stack'] ?? [];
@endphp

@extends('pdf.layout', [
    'title' => 'Website Analysis' . ($lead ? ': ' . $lead->title : ''),
    'subtitle' => $lead?->website,
])

@section('content')

    {{-- Score --}}
    @if ($score !== null)
        <div style="margin-bottom: 14px;">
            <span class="score-badge {{ $scoreClass }}">Overall Score: {{ $score }}/100</span>
            <span class="model-label">{{ $analysis->model }}</span>
        </div>
    @endif

    {{-- Business Overview --}}
    @if (!empty($result['business_overview']))
        <div class="section">
            <div class="section-title">Business Overview</div>
            <div class="section-text">{{ $result['business_overview'] }}</div>
        </div>
    @endif

    {{-- Value Proposition --}}
    @if (!empty($result['value_proposition']))
        <div class="section">
            <div class="section-title">Value Proposition</div>
            <div class="section-text">{{ $result['value_proposition'] }}</div>
        </div>
    @endif

    {{-- Sales Angles --}}
    @if (!empty($result['sales_angles']))
        <div class="section section-green">
            <div class="section-title">Sales Angles</div>
            <ul class="bullet-list">
                @foreach ((array) $result['sales_angles'] as $angle)
                    <li>{{ $angle }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Pain Points --}}
    @if (!empty($result['pain_points']))
        <div class="section section-orange">
            <div class="section-title">Pain Points</div>
            <ul class="bullet-list">
                @foreach ((array) $result['pain_points'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Competitive Position + Growth Signals --}}
    @if (!empty($result['competitive_position']) || !empty($result['growth_signals']))
        <div class="two-col">
            @if (!empty($result['competitive_position']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">Competitive Position</div>
                        <div class="section-text">{{ $result['competitive_position'] }}</div>
                    </div>
                </div>
            @endif
            @if (!empty($result['growth_signals']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">Growth Signals</div>
                        <div class="section-text">{{ $result['growth_signals'] }}</div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Tech Stack --}}
    @if (!empty($techStack))
        <div class="section section-purple">
            <div class="section-title">Tech Stack Detected</div>
            <div class="tags">
                @foreach ($techStack as $tech)
                    <span class="badge badge-purple">{{ $tech }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="meta-row">Analysed {{ $analysis->completed_at?->format('d M Y') }} · {{ $analysis->model }}</div>

@endsection
