@props(['analysis'])

@php
    $result = $analysis->result ?? [];
    $rawData = $analysis->raw_data ?? [];
    $reddit = array_slice($rawData['reddit'] ?? [], 0, 5);
    $hn = array_slice($rawData['hackernews'] ?? [], 0, 5);
    $news = array_slice($rawData['news'] ?? [], 0, 5);
@endphp

<div class="space-y-4">

    {{-- Score + Model --}}
    <div class="flex flex-wrap items-center gap-2">
        @if (isset($result['relevance_score']))
            @php
                $score = (int) $result['relevance_score'];
                $scoreColor =
                    $score >= 75
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                        : ($score >= 50
                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300');
            @endphp
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $scoreColor }}">
                <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5" />
                {{ $score }}/100
            </span>
        @endif
        <span class="text-xs text-gray-400">
            {{ __('leads.analysis_analysed_with', ['model' => $analysis->model ?? '—']) }}
        </span>
    </div>

    {{-- Market Overview --}}
    @if (!empty($result['market_overview']))
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('leads.trend_analysis_market_overview') }}
            </h4>
            <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['market_overview'] }}</p>
        </div>
    @endif

    {{-- Opportunities + Talking Points --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['opportunities']))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">
                    {{ __('leads.trend_analysis_opportunities') }}
                </h4>
                <ul class="space-y-1.5">
                    @foreach ((array) $result['opportunities'] as $opp)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-heroicon-o-light-bulb class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" />
                            {{ $opp }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($result['talking_points']))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">
                    {{ __('leads.trend_analysis_talking_points') }}
                </h4>
                <ul class="space-y-1.5">
                    @foreach ((array) $result['talking_points'] as $point)
                        <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-heroicon-o-chat-bubble-left-right class="mt-0.5 h-4 w-4 shrink-0 text-green-500" />
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Trending Topics + Sentiment --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if (!empty($result['trending_topics']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.trend_analysis_trending_topics') }}
                </h4>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ((array) $result['trending_topics'] as $topic)
                        <span
                            class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">{{ $topic }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($result['community_sentiment']))
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.trend_analysis_community_sentiment') }}
                </h4>
                <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['community_sentiment'] }}</p>
            </div>
        @endif
    </div>

    {{-- Prediction Markets --}}
    @if (!empty($result['prediction_markets']) && $result['prediction_markets'] !== 'null')
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                {{ __('leads.trend_analysis_prediction_markets') }}
            </h4>
            <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['prediction_markets'] }}</p>
        </div>
    @endif

    {{-- Source Discussions --}}
    @if (!empty($reddit) || !empty($hn) || !empty($news))
        <div x-data="{ open: false }"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button @click="open = !open"
                class="flex w-full items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-chat-bubble-oval-left-ellipsis class="h-4 w-4" />
                {{ __('leads.trend_analysis_source_discussions') }}
                @php
                    $totalDiscussions = count($reddit) + count($hn) + count($news);
                @endphp
                <span
                    class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ $totalDiscussions }}</span>
                <x-heroicon-o-chevron-down class="ml-auto h-3 w-3 transition-transform"
                    x-bind:class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open" x-transition class="mt-3 space-y-4">

                @if (!empty($reddit))
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-orange-600 dark:text-orange-400">Reddit</p>
                        <ul class="space-y-1.5">
                            @foreach ($reddit as $post)
                                <li class="flex items-start gap-2">
                                    <x-heroicon-o-arrow-up class="mt-0.5 h-3.5 w-3.5 shrink-0 text-orange-400" />
                                    <div class="min-w-0">
                                        <a href="{{ $post['url'] ?? 'https://reddit.com' . ($post['permalink'] ?? '') }}"
                                            target="_blank" rel="noopener noreferrer"
                                            class="line-clamp-1 text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $post['title'] }}
                                        </a>
                                        <span class="text-xs text-gray-400">r/{{ $post['subreddit'] }} ·
                                            {{ $post['score'] }} pts · {{ $post['comments'] }} comments</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($hn))
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-orange-700 dark:text-orange-300">Hacker News</p>
                        <ul class="space-y-1.5">
                            @foreach ($hn as $story)
                                <li class="flex items-start gap-2">
                                    <x-heroicon-o-fire class="mt-0.5 h-3.5 w-3.5 shrink-0 text-orange-500" />
                                    <div class="min-w-0">
                                        <a href="{{ $story['hn_url'] ?? ($story['url'] ?? '#') }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="line-clamp-1 text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $story['title'] }}
                                        </a>
                                        <span class="text-xs text-gray-400">{{ $story['points'] }} pts ·
                                            {{ $story['comments'] }} comments</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($news))
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-blue-600 dark:text-blue-400">Google News</p>
                        <ul class="space-y-1.5">
                            @foreach ($news as $article)
                                <li class="flex items-start gap-2">
                                    <x-heroicon-o-newspaper class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-500" />
                                    <div class="min-w-0">
                                        <a href="{{ $article['url'] }}" target="_blank" rel="noopener noreferrer"
                                            class="line-clamp-1 text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $article['title'] }}
                                        </a>
                                        @if (!empty($article['source']))
                                            <span
                                                class="text-xs text-gray-400">{{ $article['source'] }}{{ $article['published_at'] ? ' · ' . $article['published_at'] : '' }}</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- Raw data --}}
    @if (!empty($rawData))
        <details class="rounded-lg border border-gray-200 dark:border-gray-700">
            <summary
                class="cursor-pointer rounded-lg px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/60">
                {{ __('leads.trend_analysis_raw_data') }}
            </summary>
            <div class="px-4 pb-4">
                <pre
                    class="max-h-64 overflow-auto rounded bg-gray-100 p-3 font-mono text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($rawData['meta'] ?? [], JSON_PRETTY_PRINT) }}

Reddit: {{ count($rawData['reddit'] ?? []) }} posts
HN: {{ count($rawData['hackernews'] ?? []) }} stories
News: {{ count($rawData['news'] ?? []) }} articles
Polymarket: {{ count($rawData['polymarket'] ?? []) }} events</pre>
            </div>
        </details>
    @endif

</div>
