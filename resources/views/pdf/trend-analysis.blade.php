@php
    $result = $analysis->result ?? [];
    $rawData = $analysis->raw_data ?? [];
    $score = isset($result['relevance_score']) ? (int) $result['relevance_score'] : null;
    $scoreClass = $score === null ? '' : ($score >= 75 ? 'score-green' : ($score >= 50 ? 'score-yellow' : 'score-red'));
    $topic = $analysis->topic ?? null;
    $reddit = array_slice($rawData['reddit'] ?? [], 0, 5);
    $hn = array_slice($rawData['hackernews'] ?? [], 0, 5);
    $news = array_slice($rawData['news'] ?? [], 0, 5);
@endphp

@extends('pdf.layout', [
    'title' => 'Trend Analysis' . ($topic ? ': ' . $topic : ($lead ? ': ' . $lead->title : '')),
    'subtitle' => $lead?->website,
])

@section('content')

    {{-- Score --}}
    @if ($score !== null)
        <div style="margin-bottom: 14px;">
            <span class="score-badge {{ $scoreClass }}">Relevance Score: {{ $score }}/100</span>
            <span class="model-label">{{ $analysis->model }}</span>
        </div>
    @endif

    {{-- Topic --}}
    @if ($topic)
        <div class="section section-blue">
            <div class="section-title">Research Topic</div>
            <div class="section-text">{{ $topic }}</div>
        </div>
    @endif

    {{-- Market Overview --}}
    @if (!empty($result['market_overview']))
        <div class="section">
            <div class="section-title">Market Overview</div>
            <div class="section-text">{{ $result['market_overview'] }}</div>
        </div>
    @endif

    {{-- Opportunities + Talking Points --}}
    @if (!empty($result['opportunities']) || !empty($result['talking_points']))
        <div class="two-col">
            @if (!empty($result['opportunities']))
                <div class="col">
                    <div class="section section-blue" style="margin-bottom: 0;">
                        <div class="section-title">Opportunities</div>
                        <ul class="bullet-list">
                            @foreach ((array) $result['opportunities'] as $opp)
                                <li>{{ $opp }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if (!empty($result['talking_points']))
                <div class="col">
                    <div class="section section-green" style="margin-bottom: 0;">
                        <div class="section-title">Talking Points</div>
                        <ul class="bullet-list">
                            @foreach ((array) $result['talking_points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        <div style="margin-bottom: 12px;"></div>
    @endif

    {{-- Trending Topics --}}
    @if (!empty($result['trending_topics']))
        <div class="section">
            <div class="section-title">Trending Topics</div>
            <div class="tags">
                @foreach ((array) $result['trending_topics'] as $t)
                    <span class="badge badge-indigo">{{ $t }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Community Sentiment --}}
    @if (!empty($result['community_sentiment']))
        <div class="section">
            <div class="section-title">Community Sentiment</div>
            <div class="section-text">{{ $result['community_sentiment'] }}</div>
        </div>
    @endif

    {{-- Prediction Markets --}}
    @if (!empty($result['prediction_markets']) && $result['prediction_markets'] !== 'null')
        <div class="section section-amber">
            <div class="section-title">Prediction Markets</div>
            <div class="section-text">{{ $result['prediction_markets'] }}</div>
        </div>
    @endif

    {{-- Source Discussions --}}
    @if (!empty($reddit) || !empty($hn) || !empty($news))
        <div class="section">
            <div class="section-title">Source Discussions</div>

            @if (!empty($reddit))
                <div class="source-section-title">Reddit</div>
                <table class="source-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subreddit</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reddit as $post)
                            <tr>
                                <td>{{ $post['title'] }}</td>
                                <td>r/{{ $post['subreddit'] }}</td>
                                <td>{{ $post['score'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (!empty($hn))
                <div class="source-section-title">Hacker News</div>
                <table class="source-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Points</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hn as $story)
                            <tr>
                                <td>{{ $story['title'] }}</td>
                                <td>{{ $story['points'] }}</td>
                                <td>{{ $story['comments'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (!empty($news))
                <div class="source-section-title">Google News</div>
                <table class="source-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($news as $article)
                            <tr>
                                <td>{{ $article['title'] }}</td>
                                <td>{{ $article['source'] ?? '—' }}</td>
                                <td>{{ $article['published_at'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    <div class="meta-row">Analysed {{ $analysis->completed_at?->format('d M Y') }} · {{ $analysis->model }}</div>

@endsection
