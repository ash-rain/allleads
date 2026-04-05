@php
    $result = $analysis->result ?? [];
    $rawData = $analysis->raw_data ?? [];
    $score = isset($result['geo_score']) ? (int) $result['geo_score'] : null;
    $scoreClass = $score === null ? '' : ($score >= 75 ? 'score-green' : ($score >= 50 ? 'score-yellow' : 'score-red'));
    $citability = $rawData['citability'] ?? [];
    $crawlers = $rawData['robots_txt']['ai_crawlers'] ?? [];
    $brandMentions = $rawData['brand_mentions'] ?? [];
    $schemaMarkupRaw = $rawData['schema_markup'] ?? [];
    if (isset($schemaMarkupRaw['detected_types'])) {
        $schemaMarkup = array_map(fn($t) => ['type' => $t], (array) $schemaMarkupRaw['detected_types']);
    } else {
        $schemaMarkup = array_filter((array) $schemaMarkupRaw, fn($item) => is_array($item) && isset($item['type']));
    }
    $technicalSeo = $rawData['technical_seo'] ?? [];
    $llmsTxt = $rawData['llms_txt'] ?? [];
    $url = $analysis->url ?? $lead?->website;
@endphp

@extends('pdf.layout', [
    'title' => 'GEO Analysis' . ($lead ? ': ' . $lead->title : ($url ? ': ' . $url : '')),
    'subtitle' => $url,
])

@section('content')

    {{-- Score + Citability Grade --}}
    @if ($score !== null)
        <div style="margin-bottom: 14px;">
            <span class="score-badge {{ $scoreClass }}">GEO Score: {{ $score }}/100</span>
            @if (!empty($citability['grade']))
                <span class="badge badge-amber" style="margin-left: 6px;">Citability: {{ $citability['grade'] }}
                    ({{ $citability['total'] ?? 0 }}/100)</span>
            @endif
            <span class="model-label">{{ $analysis->model }}</span>
        </div>
    @endif

    {{-- AI Visibility Summary --}}
    @if (!empty($result['ai_visibility_summary']))
        <div class="section section-amber">
            <div class="section-title">AI Visibility Summary</div>
            <div class="section-text">{{ $result['ai_visibility_summary'] }}</div>
        </div>
    @endif

    {{-- Sales Angles + Quick Wins --}}
    @if (!empty($result['sales_angles']) || !empty($result['quick_wins']))
        <div class="two-col">
            @if (!empty($result['sales_angles']))
                <div class="col">
                    <div class="section section-blue" style="margin-bottom: 0;">
                        <div class="section-title">Sales Angles</div>
                        <ul class="bullet-list">
                            @foreach ((array) $result['sales_angles'] as $angle)
                                <li>{{ $angle }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if (!empty($result['quick_wins']))
                <div class="col">
                    <div class="section section-amber" style="margin-bottom: 0;">
                        <div class="section-title">Quick Wins</div>
                        <ul class="bullet-list">
                            @foreach ((array) $result['quick_wins'] as $win)
                                <li>{{ $win }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        <div style="margin-bottom: 12px;"></div>
    @endif

    {{-- Citability Assessment + Crawler Access --}}
    @if (!empty($result['citability_assessment']) || !empty($result['crawler_access_summary']))
        <div class="two-col">
            @if (!empty($result['citability_assessment']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">Citability Assessment</div>
                        <div class="section-text">{{ $result['citability_assessment'] }}</div>
                        @if (!empty($citability))
                            <div style="margin-top: 8px;">
                                @foreach (['answer_block' => 'Answer Block', 'self_containment' => 'Self-Containment', 'structural_readability' => 'Structure', 'statistical_density' => 'Data Density', 'uniqueness_signals' => 'Uniqueness'] as $key => $label)
                                    @if (isset($citability[$key]))
                                        <div class="crawler-row">
                                            <span class="crawler-name" style="color: #6b7280;">{{ $label }}</span>
                                            <span class="crawler-status"
                                                style="color: #374151;">{{ $citability[$key] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            @if (!empty($result['crawler_access_summary']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">AI Crawler Access</div>
                        <div class="section-text" style="margin-bottom: 6px;">{{ $result['crawler_access_summary'] }}</div>
                        @foreach ($crawlers as $bot => $info)
                            @php
                                $statusClass = match ($info['status']) {
                                    'allowed' => 'crawler-allowed',
                                    'blocked' => 'crawler-blocked',
                                    default => 'crawler-partial',
                                };
                            @endphp
                            <div class="crawler-row">
                                <span class="crawler-name">{{ $info['label'] }}</span>
                                <span class="crawler-status {{ $statusClass }}">{{ ucfirst($info['status']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div style="margin-bottom: 12px;"></div>
    @endif

    {{-- Brand Authority + Schema Markup --}}
    @if (!empty($result['brand_authority_assessment']) || !empty($result['schema_assessment']))
        <div class="two-col">
            @if (!empty($result['brand_authority_assessment']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">Brand Authority</div>
                        <div class="section-text" style="margin-bottom: 6px;">{{ $result['brand_authority_assessment'] }}
                        </div>
                        @php
                            $wikiFound = $brandMentions['wikipedia']['found'] ?? false;
                            $wikidataFound = $brandMentions['wikidata']['found'] ?? false;
                        @endphp
                        <span class="badge {{ $wikiFound ? 'badge-green' : 'badge-gray' }}">Wikipedia:
                            {{ $wikiFound ? 'Found' : 'Not found' }}</span>
                        <span class="badge {{ $wikidataFound ? 'badge-green' : 'badge-gray' }}"
                            style="margin-left: 4px;">Wikidata: {{ $wikidataFound ? 'Found' : 'Not found' }}</span>
                    </div>
                </div>
            @endif
            @if (!empty($result['schema_assessment']))
                <div class="col">
                    <div class="section" style="margin-bottom: 0;">
                        <div class="section-title">Schema Markup</div>
                        <div class="section-text" style="margin-bottom: 6px;">{{ $result['schema_assessment'] }}</div>
                        @if (!empty($schemaMarkup))
                            <div class="tags">
                                @foreach ($schemaMarkup as $schema)
                                    <span
                                        class="badge badge-indigo">{{ is_array($schema['type']) ? implode(' / ', $schema['type']) : $schema['type'] }}</span>
                                @endforeach
                            </div>
                        @else
                            <span style="font-size: 10px; color: #9ca3af;">No schema markup detected</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <div style="margin-bottom: 12px;"></div>
    @endif

    {{-- Technical SEO + llms.txt --}}
    @if (!empty($result['technical_assessment']))
        <div class="two-col">
            <div class="col">
                <div class="section" style="margin-bottom: 0;">
                    <div class="section-title">Technical SEO</div>
                    <div class="section-text" style="margin-bottom: 6px;">{{ $result['technical_assessment'] }}</div>
                    @if (!empty($technicalSeo))
                        <div>
                            @foreach ($technicalSeo as $key => $value)
                                @php $label = ucfirst(str_replace(['has_', '_'], ['', ' '], $key)); @endphp
                                <span
                                    class="checklist-item {{ $value ? 'checklist-pass' : 'checklist-fail' }}">{{ $label }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col">
                <div class="section" style="margin-bottom: 0;">
                    <div class="section-title">llms.txt</div>
                    @php $llmsFound = $llmsTxt['found'] ?? false; @endphp
                    <div
                        style="font-size: 12px; font-weight: 600; color: {{ $llmsFound ? '#15803d' : '#c2410c' }}; margin-bottom: 4px;">
                        {{ $llmsFound ? 'llms.txt present' : 'llms.txt not found' }}
                    </div>
                    @if (!$llmsFound)
                        <div style="font-size: 10px; color: #9ca3af;">Adding an llms.txt file helps AI systems understand
                            what content they can use from this site.</div>
                    @elseif (!empty($llmsTxt['preview']))
                        <div
                            style="font-size: 10px; font-family: monospace; background: #f9fafb; padding: 6px; border-radius: 4px; color: #374151;">
                            {{ Str::limit($llmsTxt['preview'], 200) }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div style="margin-bottom: 12px;"></div>
    @endif

    {{-- Platform Recommendations --}}
    @if (!empty($result['platform_recommendations']))
        <div class="section section-blue">
            <div class="section-title">Platform Recommendations</div>
            <ul class="bullet-list">
                @foreach ((array) $result['platform_recommendations'] as $rec)
                    <li>{{ $rec }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="meta-row">Analysed {{ $analysis->completed_at?->format('d M Y') }} · {{ $analysis->model }}</div>

@endsection
