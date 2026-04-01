<div class="space-y-6" wire:poll.5s>

    {{-- ─── Threads ─────────────────────────────────────────────────────────── --}}
    @forelse ($threads as $thread)
        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

            {{-- Thread header --}}
            <div
                class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800">
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                    <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                        <path
                            d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('emails.thread_resource_label') }} #{{ $thread->id }}
                    </span>
                    <span class="ml-2 text-xs text-gray-400">
                        {{ $thread->messages->count() }} {{ Str::plural('message', $thread->messages->count()) }}
                    </span>
                </div>
                <span @class([
                    'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' =>
                        $thread->status === 'replied',
                    'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' =>
                        $thread->status === 'closed',
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => !in_array(
                        $thread->status,
                        ['replied', 'closed']),
                ])>
                    {{ __('emails.thread_status_' . $thread->status) }}
                </span>
            </div>

            {{-- Messages --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @foreach ($thread->messages as $message)
                    @php $isOut = $message->role === 'outbound'; @endphp
                    <div @class([
                        'group relative px-5 py-4 transition-colors',
                        'border-l-4 border-primary-400 bg-primary-50/60 dark:bg-primary-900/10' => $isOut,
                        'hover:bg-gray-50 dark:hover:bg-gray-800/50' => !$isOut,
                    ])>
                        {{-- Sender row --}}
                        <div class="mb-3 flex items-start gap-3">
                            {{-- Avatar --}}
                            @php
                                $initials = strtoupper(substr($message->sender, 0, 1));
                                $avatarColor = $isOut
                                    ? 'bg-primary-500 text-white'
                                    : 'bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200';
                            @endphp
                            <div @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                $avatarColor,
                            ])>
                                {{ $initials }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $isOut ? __('emails.message_role_outbound') : __('emails.message_role_inbound') }}
                                    </span>
                                    <span class="truncate text-xs text-gray-400">{{ $message->sender }}</span>
                                    <span class="ml-auto shrink-0 text-xs text-gray-400">
                                        {{ $message->sent_at?->diffForHumans() }}
                                    </span>
                                </div>
                                @if ($message->subject)
                                    <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white">
                                        {{ $message->subject }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="prose prose-sm ml-11 max-w-none text-gray-700 dark:text-gray-300">
                            {!! nl2br(e($message->body)) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">{{ __('common.no_records') }}</p>
    @endforelse

    {{-- ─── Drafts pending approval ─────────────────────────────────────────── --}}
    @if ($drafts->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    {{ __('emails.draft_resource_label_plural') }}
                </span>
                <span
                    class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">
                    {{ $drafts->count() }}
                </span>
            </div>

            @foreach ($drafts as $draft)
                @if ($showDraftEditor && $selectedDraftId === $draft->id)
                    {{-- ── Inline editor: replaces this draft card in-place ─── --}}
                    <div class="overflow-hidden rounded-2xl border border-primary-300 bg-white shadow-md ring-2 ring-primary-100 dark:border-primary-700 dark:bg-gray-900 dark:ring-primary-900/50"
                        wire:key="draft-editor-{{ $draft->id }}">
                        {{-- Editor header --}}
                        <div
                            class="flex items-center justify-between border-b border-primary-100 bg-primary-50 px-5 py-3 dark:border-primary-800/60 dark:bg-primary-900/20">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/60">
                                    <svg class="h-4 w-4 text-primary-600 dark:text-primary-400"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $draft->subject }}</p>
                                    <p class="text-xs text-primary-600 dark:text-primary-400">
                                        {{ __('emails.draft_reviewing') }} · v{{ $draft->version }} ·
                                        {{ $draft->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <button wire:click="closeDraftEditor"
                                class="ml-4 shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                {{ __('common.cancel') }}
                            </button>
                        </div>
                        {{-- Draft editor component --}}
                        <div class="p-4">
                            <livewire:draft-editor :draft-id="$selectedDraftId" :key="'draft-' . $selectedDraftId" />
                        </div>
                    </div>
                @else
                    {{-- ── Preview card ─────────────────────────────────────── --}}
                    <div class="overflow-hidden rounded-2xl border border-yellow-300 bg-white shadow-sm dark:border-yellow-700/60 dark:bg-gray-900"
                        wire:key="draft-preview-{{ $draft->id }}">
                        <div class="border-l-4 border-yellow-400 px-5 py-4">
                            {{-- Draft header --}}
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0 text-yellow-500" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z" />
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ __('emails.draft_status_' . $draft->status) }}
                                    </span>
                                    <span
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        v{{ $draft->version }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400">{{ $draft->created_at->diffForHumans() }}</span>
                            </div>

                            <p class="mb-1 text-sm font-semibold text-gray-800 dark:text-white">{{ $draft->subject }}
                            </p>
                            <p class="text-sm text-gray-500 line-clamp-3 dark:text-gray-400">
                                {{ strip_tags($draft->body) }}</p>

                            {{-- Actions --}}
                            <div class="mt-4 flex items-center gap-2">
                                <button wire:click="openDraftEditor({{ $draft->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                        <path fill-rule="evenodd"
                                            d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ __('emails.draft_action_approve') }}
                                </button>
                                <button wire:click="deleteDraft({{ $draft->id }})"
                                    wire:confirm="{{ __('emails.draft_delete_confirm') }}" wire:loading.attr="disabled"
                                    wire:target="deleteDraft({{ $draft->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ __('emails.draft_action_delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- ─── Generating spinner ──────────────────────────────────────────────── --}}
    @if ($generating)
        <div
            class="flex items-center gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm dark:border-blue-700/60 dark:bg-blue-900/20">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                <svg class="h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">{{ __('emails.draft_generating') }}
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-400">{{ __('emails.draft_generating_hint') }}</p>
            </div>
        </div>
    @endif

    {{-- ─── Compose actions ─────────────────────────────────────────────────── --}}
    <div
        class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/60">
        <button wire:click="openManualReply"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                <path
                    d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
            </svg>
            {{ __('emails.action_send') }}
        </button>
        <button wire:click="generateAiDraft" wire:loading.attr="disabled" wire:target="generateAiDraft"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-50">
            <svg wire:loading.remove wire:target="generateAiDraft" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20" fill="currentColor">
                <path
                    d="M15.98 1.804a1 1 0 0 0-1.96 0l-.24 1.192a1 1 0 0 1-.784.785l-1.192.238a1 1 0 0 0 0 1.962l1.192.238a1 1 0 0 1 .785.785l.238 1.192a1 1 0 0 0 1.962 0l.238-1.192a1 1 0 0 1 .785-.785l1.192-.238a1 1 0 0 0 0-1.962l-1.192-.238a1 1 0 0 1-.785-.785l-.238-1.192ZM6.949 5.684a1 1 0 0 0-1.898 0l-.683 2.051a1 1 0 0 1-.633.633l-2.051.683a1 1 0 0 0 0 1.898l2.051.684a1 1 0 0 1 .633.632l.683 2.051a1 1 0 0 0 1.898 0l.683-2.051a1 1 0 0 1 .633-.633l2.051-.683a1 1 0 0 0 0-1.898l-2.051-.683a1 1 0 0 1-.633-.633L6.95 5.684ZM13.949 13.684a1 1 0 0 0-1.898 0l-.184.551a1 1 0 0 1-.632.633l-.551.183a1 1 0 0 0 0 1.898l.551.183a1 1 0 0 1 .633.633l.183.551a1 1 0 0 0 1.898 0l.184-.551a1 1 0 0 1 .632-.633l.551-.183a1 1 0 0 0 0-1.898l-.551-.184a1 1 0 0 1-.633-.632l-.183-.551Z" />
            </svg>
            <svg wire:loading wire:target="generateAiDraft" class="h-4 w-4 animate-spin"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                    stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="generateAiDraft">{{ __('emails.action_generate') }}</span>
            <span wire:loading wire:target="generateAiDraft">{{ __('common.generating') }}…</span>
        </button>
    </div>

    {{-- ─── Manual reply compose ────────────────────────────────────────────── --}}
    @if ($showManualReply)
        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div
                class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800">
                <span class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path
                            d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                        <path
                            d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                    </svg>
                    {{ __('emails.action_send') }}
                </span>
                <button wire:click="$set('showManualReply', false)"
                    class="rounded-md px-2 py-1 text-xs text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700">
                    {{ __('common.cancel') }}
                </button>
            </div>
            <div class="p-4">
                <input type="text" wire:model="manualReplySubject"
                    placeholder="{{ __('emails.draft_field_subject') }}"
                    class="mb-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" />
                <textarea wire:model="manualReplyBody" rows="6"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"></textarea>
                <div class="mt-4 flex justify-end gap-3">
                    <button wire:click="$set('showManualReply', false)"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="saveManualReply"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                        {{ __('common.send') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
