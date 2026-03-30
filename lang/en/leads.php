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
    'import_file' => 'CSV File',
    'import_assign_to' => 'Assign To',
    'import_started' => 'Import started — you will be notified when complete.',
    'action_generate_emails' => 'Generate Cold Emails',
    'action_assign' => 'Assign',
    'action_change_status' => 'Change Status',
    'action_add_tag' => 'Add Tag',
    'action_remove_tag' => 'Remove Tag',
    'action_analyse_lead' => 'Analyse Lead',
    'action_analyse_leads' => 'Analyse with AI',

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
    'tab_intelligence' => 'Intelligence',

    // Prospect analysis
    'analysis_no_data' => 'No analysis yet',
    'analysis_no_data_hint' => 'Click "Analyse Lead" to run AI prospect intelligence.',
    'analysis_pending' => 'Analysis in progress…',
    'analysis_pending_hint' => 'This page will refresh automatically.',
    'analysis_failed' => 'Analysis failed',
    'analysis_retry' => 'Retry Analysis',
    'analysis_score' => 'Prospect Score',
    'analysis_company_fit' => 'Company Fit',
    'analysis_contact_intel' => 'Contact Intelligence',
    'analysis_opportunity' => 'Opportunity',
    'analysis_competitive_intel' => 'Competitive Intelligence',
    'analysis_outreach_strategy' => 'Outreach Strategy',
    'analysis_analysed_with' => 'Analysed with :model',
    'analysis_completed_at' => 'Analysed :date',
    'analysis_confirm_heading' => 'Analyse Lead',
    'analysis_confirm_body' => 'This will run AI prospect intelligence on the lead. Results will appear in the Intelligence tab.',
    'analysis_bulk_confirm_body' => 'This will queue AI prospect analysis for each selected lead. You will be notified if any fail.',
    'analysis_queued' => 'Analysis queued — results will appear shortly.',
    'analysis_queued_plural' => '{0} No leads queued.|{1} :count lead queued for analysis.|[2,*] :count leads queued for analysis.',

    // Notes
    'note_type_note' => 'Note',
    'note_type_call' => 'Call Log',
    'call_duration' => 'Duration (minutes)',
    'call_outcome' => 'Outcome',
    'call_outcome_interested' => 'Interested',
    'call_outcome_not_interested' => 'Not Interested',
    'call_outcome_no_answer' => 'No Answer',
    'call_outcome_callback' => 'Callback Requested',

    // Dashboard widgets
    'web_dev_prospects' => 'Web Dev Prospects',
    'high_rating_no_website' => 'High rating, no website',

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
