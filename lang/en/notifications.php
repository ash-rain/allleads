<?php

return [
    // Import finished
    'import_completed_title'   => 'Import Complete',
    'import_completed_body'    => ':created new leads added, :updated updated, :skipped skipped.',

    // Import failed
    'import_failed_title'      => 'Import Failed',
    'import_failed_body'       => 'The import batch ":filename" encountered an error.',

    // Email sent
    'email_sent_title'         => 'Email Sent',
    'email_sent_body'          => 'Your email to ":lead" was delivered.',

    // Email failed
    'email_failed_title'       => 'Email Delivery Failed',
    'email_failed_body'        => 'Could not deliver email to ":lead": :error',

    // Lead replied
    'lead_replied_title'       => ':lead Replied',
    'lead_replied_body'        => '":lead" has replied to your email.',

    // Draft ready
    'draft_ready_title'        => 'Draft Ready',
    'draft_ready_body'         => 'AI draft generated for ":lead". Review before sending.',

    // Draft failed
    'draft_failed_title'       => 'Draft Generation Failed',
    'draft_failed_body'        => 'Could not generate draft for ":lead": :error',

    // Campaign finished
    'campaign_completed_title' => 'Campaign Complete',
    'campaign_completed_body'  => 'Campaign ":name" finished. :count emails generated.',
];
