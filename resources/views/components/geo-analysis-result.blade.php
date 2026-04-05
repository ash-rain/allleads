@props(['analysis'])

@php
    $result = $analysis->result ?? [];
    $rawData = $analysis->raw_data ?? [];
    $citability = $rawData['citability'] ?? [];
    $crawlers = $rawData['robots_txt']['ai_crawlers'] ?? [];
    $brandMentions = $rawData['brand_mentions'] ?? [];
    $schemaMarkup = $rawData['schema_markup'] ?? [];
    $technicalSeo = $rawData['technical_seo'] ?? [];
    $llmsTxt = $rawData['llms_txt'] ?? [];
@endphp

<div class="space-y-4">

    {{-- GEO Score + Model --}}
    <div class="flex flex-wrap items-center gap-2">
        @if (isset($result['geo_score']))
            @php
                $score = (int) $result['geo_score'];
                $scoreColor =
                    $score >= 75
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                        : ($score >= 50
                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300');
            @endphp
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $scoreColor }}">
                <x-heroicon-o-signal class="h-3.5 w-3.5" />
                GEO {{ $score }}/100
            </span>
        @endif
        @if (!empty($citability['grade']))
            <span
                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                Citability: {{ $citability['grade'] }} ({{ $citability['total'] ?? 0 }}/100)
            </span>
        @endif
        <span class="text-xs text-gray-400">
            {{ __('leads.analysis_analysed_with', ['model' => $analysis->model ?? '—']) }}
        </span>
    </div>

    {{-- AI Visibility Summary --}}
    @if (!empty($result['ai_visibility_summary']))
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                {{ __('leads.geo_analysis_ai_visibility') }}
            </h4>
            <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['ai_visibility_summary'] }}</p>
        </div>
    @endif

    {{-- Sales Angles + Quick Wins --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['sales_angles']))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">
                    {{ __('leads.geo_analysis_sales_angles') }}
                </h4>
                <ul class="space-y-1.5">
                    @foreach ((array) $result['sales_angles'] as $angle)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-heroicon-o-light-bulb class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" />
                            {{ $angle }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($result['quick_wins']))
            <div
                class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                    {{ __('leads.geo_analysis_quick_wins') }}
                </h4>
                <ul class="space-y-1.5">
                    @foreach ((array) $result['quick_wins'] as $win)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-heroicon-o-bolt class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                            {{ $win }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Detailed Assessments --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['citability_assessment']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.geo_analysis_citability') }}
                </h4>
                <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['citability_assessment'] }}</p>
                @if (!empty($citability))
                    <div class="mt-3 grid grid-cols-2 gap-1">
                        @foreach (['answer_block' => 'Answer Block', 'self_containment' => 'Self-Containment', 'structural_readability' => 'Structure', 'statistical_density' => 'Data Density', 'uniqueness_signals' => 'Uniqueness'] as $key => $label)
                            @if (isset($citability[$key]))
                                <div class="flex items-center justify-between rounded bg-gray-50 px-2 py-1 dark:bg-gray-700">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $citability[$key] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if (!empty($result['crawler_access_summary']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.geo_analysis_crawler_access') }}
                </h4>
                <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $result['crawler_access_summary'] }}</p>
                @if (!empty($crawlers))
                    <div class="space-y-1">
                        @foreach ($crawlers as $bot => $info)
                            @php
                                $statusClass = match ($info['status']) {
                                    'allowed' => 'text-green-600 dark:text-green-400',
                                    'blocked' => 'text-red-600 dark:text-red-400',
                                    'partial' => 'text-yellow-600 dark:text-yellow-400',
                                    default => 'text-gray-400',
                                };
                                $statusIcon = match ($info['status']) {
                                    'allowed' => 'heroicon-o-check-circle',
                                    'blocked' => 'heroicon-o-x-circle',
                                    'partial' => 'heroicon-o-exclamation-circle',
                                    default => 'heroicon-o-question-mark-circle',
                                };
                            @endphp
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-300">{{ $info['label'] }}</span>
                                <span class="flex items-center gap-1 text-xs font-medium {{ $statusClass }}">
                                    <x-dynamic-component :component="$statusIcon" class="h-3.5 w-3.5" />
                                    {{ ucfirst($info['status']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Brand Authority + Schema --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['brand_authority_assessment']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.geo_analysis_brand_authority') }}
                </h4>
                <p class="mb-2 text-sm text-gray-700 dark:text-gray-200">{{ $result['brand_authority_assessment'] }}</p>
                @php
                    $wikiFound = $brandMentions['wikipedia']['found'] ?? false;
                    $wikidataFound = $brandMentions['wikidata']['found'] ?? false;
                @endphp
                <div class="flex gap-2">
                    <span
                        class="rounded-full px-2 py-0.5 text-xs {{ $wikiFound ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        Wikipedia: {{ $wikiFound ? 'Found' : 'Not found' }}
                    </span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs {{ $wikidataFound ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        Wikidata: {{ $wikidataFound ? 'Found' : 'Not found' }}
                    </span>
                </div>
            </div>
        @endif

        @if (!empty($result['schema_assessment']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.geo_analysis_schema') }}
                </h4>
                <p class="mb-2 text-sm text-gray-700 dark:text-gray-200">{{ $result['schema_assessment'] }}</p>
                @if (!empty($schemaMarkup))
                    <div class="flex flex-wrap gap-1">
                        @foreach ($schemaMarkup as $schema)
                            <span
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $schema['type'] }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400">No schema markup detected</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Technical SEO + llms.txt --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['technical_assessment']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.geo_analysis_technical') }}
                </h4>
                <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $result['technical_assessment'] }}</p>
                @if (!empty($technicalSeo))
                    <div class="grid grid-cols-2 gap-1">
                        @foreach ($technicalSeo as $key => $value)
                            @php $label = str_replace(['has_', '_'], ['', ' '], $key); @endphp
                            <span
                                class="flex items-center gap-1 rounded px-2 py-0.5 text-xs {{ $value ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' }}">
                                @if ($value)
                                    <x-heroicon-o-check class="h-3 w-3" />
                                @else
                                    <x-heroicon-o-x-mark class="h-3 w-3" />
                                @endif
                                {{ ucfirst($label) }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('leads.geo_analysis_llms_txt') }}
            </h4>
            @php $llmsFound = $llmsTxt['found'] ?? false; @endphp
            <div class="flex items-center gap-2 mb-2">
                @if ($llmsFound)
                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">llms.txt present</span>
                @else
                    <x-heroicon-o-x-circle class="h-5 w-5 text-orange-400" />
                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400">llms.txt not found</span>
                @endif
            </div>
            @if (!$llmsFound)
                <p class="text-xs text-gray-400">Adding an llms.txt file helps AI systems understand what content they
                    can use from this site.</p>
            @elseif (!empty($llmsTxt['preview']))
                <p class="rounded bg-gray-50 p-2 font-mono text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ Str::limit($llmsTxt['preview'], 200) }}
                </p>
            @endif
        </div>
    </div>

    {{-- Platform Recommendations --}}
    @if (!empty($result['platform_recommendations']))
        <div
            class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                {{ __('leads.geo_analysis_platform_recommendations') }}
            </h4>
            <ul class="space-y-1.5">
                @foreach ((array) $result['platform_recommendations'] as $rec)
                    <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-heroicon-o-arrow-right class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" />
                        {{ $rec }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
