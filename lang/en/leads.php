<?php

return [
    // Table columns
    'field_title' => 'Business Name',
    'field_category' => 'Category',
    'field_address' => 'Address',
    'field_phone' => 'Phone',
    'field_website' => 'Website',
    'field_email' => 'Email',
    'field_review_rating' => 'Rating',
    'field_status' => 'Status',
    'field_assignee' => 'Assignee',
    'field_tags' => 'Tags',
    'field_source' => 'Source',
    'field_import_batch' => 'Import Batch',
    'field_created_at' => 'Imported',

    // Statuses
    'status_new' => 'New',
    'status_contacted' => 'Contacted',
    'status_replied' => 'Replied',
    'status_closed' => 'Closed',
    'status_disqualified' => 'Disqualified',

    // Sources
    'source_csv' => 'CSV Import',
    'source_json' => 'JSON Import',
    'source_manual' => 'Manual',

    // Resource labels
    'resource_label' => 'Lead',
    'resource_label_plural' => 'Leads',
    'nav_label' => 'Leads',

    // Actions
    'action_import' => 'Import Leads',
    'action_generate_emails' => 'Generate Cold Emails',
    'action_assign' => 'Assign',
    'action_change_status' => 'Change Status',
    'action_add_tag' => 'Add Tag',
    'action_remove_tag' => 'Remove Tag',

    // Filters
    'filter_has_website' => 'Has Website',
    'filter_no_website' => 'No Website',
    'filter_has_email' => 'Has Email',
    'filter_rating_min' => 'Min Rating',
    'filter_rating_max' => 'Max Rating',
    'filter_status' => 'Status',
    'filter_category' => 'Category',
    'filter_assignee' => 'Assignee',
    'filter_tags' => 'Tags',
    'filter_import_batch' => 'Import Batch',
    'filter_date_from' => 'Imported From',
    'filter_date_to' => 'Imported To',

    // Detail tabs
    'tab_overview' => 'Overview',
    'tab_conversation' => 'Conversation',
    'tab_notes' => 'Notes & Calls',
    'tab_activity' => 'Activity',

    // Notes
    'note_type_note' => 'Note',
    'note_type_call' => 'Call Log',
    'call_duration' => 'Duration (minutes)',
    'call_outcome' => 'Outcome',
    'call_outcome_interested' => 'Interested',
    'call_outcome_not_interested' => 'Not Interested',
    'call_outcome_no_answer' => 'No Answer',
    'call_outcome_callback' => 'Callback Requested',

    // Activity events
    'activity_created' => 'Lead imported',
    'activity_status_changed' => 'Status changed from :from to :to',
    'activity_tag_added' => 'Tag ":tag" added',
    'activity_tag_removed' => 'Tag ":tag" removed',
    'activity_assignee_changed' => 'Assignee changed',
    'activity_email_sent' => 'Email sent',
    'activity_reply_received' => 'Reply received',
    'activity_note_added' => 'Note added',

    // Web Dev Prospects preset
    'preset_web_dev_prospects' => 'Web Dev Prospects',
    'preset_web_dev_help' => 'Businesses with rating > 4.5 and no website',
];
