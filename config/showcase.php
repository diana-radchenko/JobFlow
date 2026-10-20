<?php

return [
    // Use dedicated, low-privilege test accounts. Never use real customer accounts.
    'candidate_email' => env('SHOWCASE_CANDIDATE_EMAIL', ''),
    'candidate_password' => env('SHOWCASE_CANDIDATE_PASSWORD', ''),
    'employer_email' => env('SHOWCASE_EMPLOYER_EMAIL', ''),
    'employer_password' => env('SHOWCASE_EMPLOYER_PASSWORD', ''),
    'video_url' => env('SHOWCASE_VIDEO_URL', ''),
    'presentation_url' => env('SHOWCASE_PRESENTATION_URL', ''),
];
