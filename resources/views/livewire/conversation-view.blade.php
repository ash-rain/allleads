<div class="space-y-6" wire:poll.5s>
    {{-- Threads --}}
    @forelse ($threads as $thread)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('emails.thread_resource_label') }} #{{ $thread->id }}
                </span>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-semibold
                {{ $thread->status === 'replied'
                    ? 'bg-yellow-100 text-yellow-700'
                    : ($thread->status === 'closed'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-blue-100 text-blue-700') }}">
                    {{ __('emails.thread_status_' . $thread->status) }}
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($thread->messages as $message)
                    <div
                        class="px-4 py-3 {{ $message->role === 'outbound' ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                        <div class="mb-1 flex items-baseline justify-between">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ $message->role === 'outbound' ? __('emails.message_role_outbound') : __('emails.message_role_inbound') }}
                                · {{ $message->sender }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ $message->sent_at?->diffForHumans() }}
                            </span>
                        </div>
                        @if ($message->subject)
                            <p class="mb-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $message->subject }}
                            </p>
                        @endif
                        <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-200">
                            {!! nl2br(e($message->body)) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">{{ __('common.no_records') }}</p>
    @endforelse

    {{-- Drafts pending approval --}}
    @foreach ($drafts as $draft)
        <div
            class="rounded-xl border border-yellow-300 bg-yellow-50 p-4 shadow-sm dark:border-yellow-700 dark:bg-yellow-900/20">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                    {{ __('emails.draft_resource_label') }} — {{ __('emails.draft_status_' . $draft->status) }}
                    <span class="ml-2 text-xs text-gray-500">v{{ $draft->version }}</span>
                </span>
            </div>
            <p class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $draft->subject }}</p>
            <p class="text-sm text-gray-600 line-clamp-3 dark:text-gray-300">{{ strip_tags($draft->body) }}</p>
            <div class="mt-3 flex gap-3">
                <button wire:click="openDraftEditor({{ $draft->id }})"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">
                    {{ __('emails.draft_action_approve') }}
                </button>
            </div>
        </div>
    @endforeach

    {{-- Draft Editor --}}
    @if ($showDraftEditor && $selectedDraftId)
        <div
            class="rounded-xl border border-primary-300 bg-white p-4 shadow-sm dark:border-primary-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('emails.draft_action_approve') }}
                </span>
                <button wire:click="closeDraftEditor"
                    class="rounded-md px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    {{ __('common.cancel') }}
                </button>
            </div>
            <livewire:draft-editor :draft-id="$selectedDraftId" :key="'draft-' . $selectedDraftId" />
        </div>
    @endif

    {{-- Reply controls --}}
    <div class="flex gap-3">
        <button wire:click="openManualReply"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            {{ __('emails.action_send') }}
        </button>
        <button wire:click="generateAiDraft" wire:loading.attr="disabled" wire:target="generateAiDraft"
            class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50 transition">
            <span wire:loading.remove wire:target="generateAiDraft">{{ __('emails.action_generate') }}</span>
            <span wire:loading wire:target="generateAiDraft">{{ __('common.generating') }}…</span>
        </button>
    </div>

    {{-- Manual reply compose --}}
    @if ($showManualReply)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <input type="text" wire:model="manualReplySubject" placeholder="{{ __('emails.draft_field_subject') }}"
                class="mb-2 w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <textarea wire:model="manualReplyBody" rows="5"
                class="w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
            <div class="mt-3 flex justify-end gap-3">
                <button wire:click="$set('showManualReply', false)"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    {{ __('common.cancel') }}
                </button>
                <button wire:click="saveManualReply"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    {{ __('common.send') }}
                </button>
            </div>
        </div>
    @endif
</div>
