<?php

return [
    'industries' => [
        'Technology / Software', 'IT Services', 'Cybersecurity', 'Education & Training',
        'Engineering', 'Manufacturing', 'Healthcare', 'Financial Services',
        'Professional Services', 'Retail & E-commerce', 'Media & Communications',
        'Energy & Utilities', 'Transportation & Logistics', 'Hospitality & Tourism',
        'Government & Public Sector', 'Nonprofit', 'Construction & Real Estate',
        'Science & Research', 'Telecommunications', 'Other',
    ],
    // Previous controlled values remain queryable without rewriting production data.
    'industry_aliases' => [
        'Technology / Software' => ['Technology & Software'],
        'Healthcare' => ['Healthcare & Life Sciences'],
        'Financial Services' => ['Financial Services & Banking'],
        'Professional Services' => ['Professional Services & Consulting'],
        'Nonprofit' => ['Nonprofit & Social Services'],
        'Science & Research' => ['Research & Science'],
    ],
    'position_levels' => ['Junior', 'Middle', 'Senior', 'Lead', 'Manager', 'Executive'],
    'employment_types' => ['Full-time', 'Part-time', 'Contract', 'Internship', 'Temporary', 'Freelance'],
    'workplace_types' => ['Remote', 'Hybrid', 'On-site'],
    'timezones' => [
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'Europe/London', 'Europe/Brussels', 'Europe/Moscow', 'Asia/Dubai',
        'Asia/Singapore', 'Asia/Tokyo',
    ],
];
