<div class="space-y-2">
    @forelse ($activities as $activity)
        <div class="flex items-start gap-3 py-2">
            <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary-500"></div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    @php
                        $msg = match ($activity->event) {
                            'created' => __('leads.activity_created'),
                            'status_changed' => __('leads.activity_status_changed', [
                                'from' => $activity->payload['from'] ?? '',
                                'to' => $activity->payload['to'] ?? '',
                            ]),
                            'tag_added' => __('leads.activity_tag_added', ['tag' => $activity->payload['tag'] ?? '']),
                            'tag_removed' => __('leads.activity_tag_removed', [
                                'tag' => $activity->payload['tag'] ?? '',
                            ]),
                            'assignee_changed' => __('leads.activity_assignee_changed'),
                            'email_sent' => __('leads.activity_email_sent'),
                            'reply_received' => __('leads.activity_reply_received'),
                            'note_added' => __('leads.activity_note_added'),
                            default => $activity->event,
                        };
                    @endphp
                    {{ $msg }}
                </p>
                <p class="mt-0.5 text-xs text-gray-400">
                    {{ $activity->created_at?->diffForHumans() }}
                </p>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">{{ __('common.no_records') }}</p>
    @endforelse
</div>
