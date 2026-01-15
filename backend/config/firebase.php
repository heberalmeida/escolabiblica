<?php
return [
    'project_id'        => env('FIREBASE_PROJECT_ID'),
    'api_key'           => env('FIREBASE_API_KEY'),
    'collection'        => env('FIREBASE_COLLECTION', 'webhooks'),
    'collection_prefix' => env('FIREBASE_COLLECTION_PREFIX', ''),
];
