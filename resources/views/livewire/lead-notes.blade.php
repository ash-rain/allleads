<div class="space-y-6">
    {{-- Add note form --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 flex gap-4">
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" wire:model="type" value="note" class="accent-primary-600">
                {{ __('leads.note_type_note') }}
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" wire:model="type" value="call" class="accent-primary-600">
                {{ __('leads.note_type_call') }}
            </label>
        </div>

        <textarea wire:model="body" rows="3" placeholder="{{ __('leads.note_type_note') }}…"
            class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>

        @if ($type === 'call')
            <div class="mt-3 grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ __('leads.call_duration') }}
                    </label>
                    <input type="number" wire:model="duration" min="0"
                        class="w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ __('leads.call_outcome') }}
                    </label>
                    <select wire:model="outcome"
                        class="w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">—</option>
                        <option value="interested">{{ __('leads.call_outcome_interested') }}</option>
                        <option value="not_interested">{{ __('leads.call_outcome_not_interested') }}</option>
                        <option value="no_answer">{{ __('leads.call_outcome_no_answer') }}</option>
                        <option value="callback">{{ __('leads.call_outcome_callback') }}</option>
                    </select>
                </div>
            </div>
        @endif

        <div class="mt-3 text-right">
            <button wire:click="addNote"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                {{ __('common.save') }}
            </button>
        </div>
    </div>

    {{-- Note list --}}
    <div class="space-y-3">
        @forelse ($notes as $note)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold
                    {{ $note->type === 'call' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $note->type === 'call' ? __('leads.note_type_call') : __('leads.note_type_note') }}
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ $note->creator?->name }} · {{ $note->created_at->diffForHumans() }}
                    </span>
                </div>
                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $note->body }}</p>
                @if ($note->type === 'call')
                    <div class="mt-2 flex gap-4 text-xs text-gray-500">
                        @if ($note->duration_minutes)
                            <span>⏱ {{ $note->duration_minutes }} min</span>
                        @endif
                        @if ($note->outcome)
                            <span>📋 {{ __('leads.call_outcome_' . $note->outcome) }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ __('common.no_records') }}</p>
        @endforelse
    </div>
</div>
