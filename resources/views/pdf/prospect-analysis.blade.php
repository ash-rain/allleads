@php
    $result = $analysis->result ?? [];
    $score = isset($result['prospect_score']) ? (int) $result['prospect_score'] : null;
    $scoreClass = $score === null ? '' : ($score >= 75 ? 'score-green' : ($score >= 50 ? 'score-yellow' : 'score-red'));
@endphp

@extends('pdf.layout', [
    'title' => 'Prospect Analysis' . ($lead ? ': ' . $lead->title : ''),
    'subtitle' => $lead?->website,
])

@section('content')
    {{-- Score --}}
    @if ($score !== null)
        <div style="margin-bottom: 14px;">
            <span class="score-badge {{ $scoreClass }}">Prospect Score: {{ $score }}/100</span>
            <span class="model-label">{{ $analysis->model }}</span>
        </div>
    @endif

    {{-- Company Fit --}}
    @if (!empty($result['company_fit']))
        <div class="section">
            <div class="section-title">Company Fit</div>
            <div class="section-text">{{ $result['company_fit'] }}</div>
        </div>
    @endif

    {{-- Contact Intel --}}
    @if (!empty($result['contact_intel']))
        <div class="section">
            <div class="section-title">Contact Intelligence</div>
            <div class="section-text">{{ $result['contact_intel'] }}</div>
        </div>
    @endif

    {{-- Opportunity --}}
    @if (!empty($result['opportunity']))
        <div class="section section-green">
            <div class="section-title">Opportunity</div>
            <div class="section-text">{{ $result['opportunity'] }}</div>
        </div>
    @endif

    {{-- Competitive Intel --}}
    @if (!empty($result['competitive_intel']))
        <div class="section">
            <div class="section-title">Competitive Intelligence</div>
            <div class="section-text">{{ $result['competitive_intel'] }}</div>
        </div>
    @endif

    {{-- Outreach Strategy --}}
    @if (!empty($result['outreach_strategy']))
        <div class="section section-blue">
            <div class="section-title">Outreach Strategy</div>
            <div class="section-text">{{ $result['outreach_strategy'] }}</div>
        </div>
    @endif

    <div class="meta-row">Analysed {{ $analysis->completed_at?->format('d M Y') }} · {{ $analysis->model }}</div>
@endsection
