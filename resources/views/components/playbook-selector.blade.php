@props(['options' => [], 'selected' => null])

<div class="flex items-center gap-3 px-4 py-2">
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
        {{ __('playbooks.selector_label') }}
    </span>

    <select
        wire:change="applyPlaybook($event.target.value ? Number($event.target.value) : null)"
        class="block w-56 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
    >
        <option value="">— {{ __('playbooks.selector_no_playbook') }} —</option>

        @foreach ($options as $id => $name)
            <option value="{{ $id }}" @selected($selected == $id)>{{ $name }}</option>
        @endforeach
    </select>

    @if ($selected)
        <span class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900 dark:text-primary-300">
            {{ __('playbooks.selector_active') }}
        </span>
    @endif
</div>
