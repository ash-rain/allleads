<div wire:poll.5s="load">
    @if ($batch)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-2 flex items-center justify-between">
                <span class="font-medium text-sm text-gray-700 dark:text-gray-200">{{ $batch['filename'] }}</span>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-semibold
                {{ $batch['status'] === 'completed'
                    ? 'bg-green-100 text-green-700'
                    : ($batch['status'] === 'failed'
                        ? 'bg-red-100 text-red-700'
                        : 'bg-blue-100 text-blue-700') }}">
                    {{ $batch['status'] }}
                </span>
            </div>

            {{-- Progress bar --}}
            @if ($batch['total'] > 0)
                <div class="mt-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600" style="height:6px">
                    <div class="h-full rounded-full bg-primary-500 transition-all duration-500"
                        style="width: {{ round(($batch['progress'] / $batch['total']) * 100) }}%"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $batch['progress'] }} / {{ $batch['total'] }}
                </p>
            @endif

            {{-- Summary --}}
            @if ($batch['status'] === 'completed')
                <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                    <div>
                        <div class="font-semibold text-green-600">{{ $batch['created_count'] }}</div>
                        <div class="text-gray-500">{{ __('common.created') }}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-blue-600">{{ $batch['updated_count'] }}</div>
                        <div class="text-gray-500">{{ __('common.updated') }}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-yellow-600">{{ $batch['skipped_count'] }}</div>
                        <div class="text-gray-500">{{ __('common.skipped') }}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-red-600">{{ $batch['failed_count'] }}</div>
                        <div class="text-gray-500">{{ __('common.failed') }}</div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-gray-400">{{ __('common.no_records') }}</p>
    @endif
</div>
