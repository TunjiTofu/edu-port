<?php

/**
 * Tutorial Videos — MG Portfolio Landing Page
 *
 * Each entry needs only a YouTube URL. The thumbnail is pulled automatically
 * from YouTube's image CDN (no API key required).
 *
 * To find a video ID: https://www.youtube.com/watch?v=VIDEO_ID_IS_HERE
 *
 * Groups:
 *   'candidate' → shown in the "Candidates" tab
 *   'reviewer'  → shown in the "Reviewers" tab
 *   'observer'  → shown in the "Observers" tab
 *   'general'   → shown in the "Getting Started" tab (visible to all)
 */

return [
    [
        'group'       => 'general',
        'group_label' => 'Getting Started',
        'title'       => 'Welcome to MG Portfolio — Platform Overview',
        'description' => 'A quick introduction to the MG Portfolio portal and how it works.',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // replace with real URL
        'duration'    => '3:42',
    ],
    [
        'group'       => 'candidate',
        'group_label' => 'Candidates',
        'title'       => 'How to Register as a Candidate',
        'description' => 'Step-by-step guide to creating your candidate account and setting up your profile.',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // replace with real URL
        'duration'    => '4:15',
    ],
    [
        'group'       => 'candidate',
        'group_label' => 'Candidates',
        'title'       => 'How to Submit a Task',
        'description' => 'Learn how to view tasks, upload your work, and track your submission status.',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // replace with real URL
        'duration'    => '5:30',
    ],
    [
        'group'       => 'reviewer',
        'group_label' => 'Reviewers',
        'title'       => 'How to Review Submissions',
        'description' => 'A guide for reviewers on accessing submissions, scoring with rubrics, and leaving feedback.',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // replace with real URL
        'duration'    => '6:00',
    ],
    [
        'group'       => 'observer',
        'group_label' => 'Observers',
        'title'       => 'Observer Dashboard Guide',
        'description' => 'How to monitor candidate progress and review results as an observer.',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // replace with real URL
        'duration'    => '3:20',
    ],
];
