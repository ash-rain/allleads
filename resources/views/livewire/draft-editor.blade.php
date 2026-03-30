<div x-data="{ tab: 'editor' }" @draft-refined.window="tab = 'editor'" class="flex flex-col gap-4"
    @if ($awaitingRefine) wire:poll.3s="pollForRefine" @endif
>

    {{-- Status flash --}}
    @if ($statusMessage)
        <div @class([
            'rounded-lg px-4 py-2 text-sm font-medium',
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' =>
                $statusType === 'success',
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' =>
                $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    {{-- AI refinement in-progress indicator --}}
    @if ($awaitingRefine)
        <div class="flex items-center gap-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 px-4 py-2 text-sm text-amber-800 dark:text-amber-300">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            AI is working on your refinement…
        </div>
    @endif

    {{-- Header bar --}}
    <div class="flex items-center justify-between gap-2">
        <div class="flex gap-2">
            <button x-on:click="tab = 'editor'"
                :class="tab === 'editor' ? 'bg-brand-blue text-white' :
                    'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'"
                class="rounded-md px-3 py-1.5 text-xs font-medium transition">
                Editor
            </button>
            <button x-on:click="tab = 'refine'"
                :class="tab === 'refine' ? 'bg-brand-blue text-white' :
                    'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'"
                class="rounded-md px-3 py-1.5 text-xs font-medium transition">
                AI Refine
            </button>
        </div>

        <div class="flex gap-2">
            <button wire:click="toggleVersions"
                class="rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                History ({{ count($versions) }})
            </button>
            <button wire:click="send" wire:loading.attr="disabled" @disabled($draft?->status === 'sent' || $sending)
                class="rounded-md bg-brand-teal text-white px-3 py-1.5 text-xs font-medium hover:opacity-90 disabled:opacity-50 transition">
                {{ $draft?->status === 'sent' ? 'Sent' : 'Send Email' }}
            </button>
        </div>
    </div>

    {{-- Split pane: Editor + Preview --}}
    <div x-show="tab === 'editor'" class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Left: Edit form --}}
        <div class="flex flex-col gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Subject</label>
                <input wire:model="subject" type="text"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    placeholder="Email subject…">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Body</label>
                <textarea wire:model="body" rows="18"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-blue font-mono resize-y"
                    placeholder="Email body…"></textarea>
            </div>
            <button wire:click="save"
                class="self-end rounded-md bg-brand-blue text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
                Save Draft
            </button>
        </div>

        {{-- Right: Live preview --}}
        <div class="flex flex-col gap-1">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Preview</p>
            <div
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm h-full overflow-auto">
                @if ($subject)
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-2">Subject: {{ $subject }}
                    </p>
                    <hr class="mb-3 border-gray-200 dark:border-gray-700">
                @endif
                <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
                    {{ $body }}</div>
            </div>
        </div>
    </div>

    {{-- AI Refine tab --}}
    <div x-show="tab === 'refine'" class="flex flex-col gap-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Describe the change you want the AI to make to this draft. For example: <em>"Make it shorter"</em>, <em>"Add
                a P.S. line"</em>, <em>"Change tone to casual"</em>.
        </p>
        <textarea wire:model="refineInput" rows="4"
            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-blue resize-y"
            placeholder="e.g. Make it shorter and punchier…"></textarea>
        <button wire:click="refine" wire:loading.attr="disabled" @disabled($refining)
            class="self-start rounded-md bg-brand-orange text-white px-4 py-2 text-sm font-medium hover:opacity-90 disabled:opacity-50 transition">
            <span wire:loading.remove wire:target="refine">Refine with AI</span>
            <span wire:loading wire:target="refine">Refining…</span>
        </button>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            The AI will re-generate the body. Results appear automatically in the Editor tab once complete (usually under 30 s). A snapshot of the current version is saved to History before overwriting.
        </p>
    </div>

    {{-- Version history panel --}}
    @if ($showVersions)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Version History</p>
            @forelse ($versions as $v)
                <div
                    class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        v{{ $v['version'] }} — {{ $v['created_at'] }}
                    </span>
                    <button wire:click="restoreVersion({{ $v['version'] }})"
                        class="text-xs text-brand-blue hover:underline">
                        Restore
                    </button>
                </div>
            @empty
                <p class="text-xs text-gray-400">No previous versions.</p>
            @endforelse
        </div>
    @endif
</div>
