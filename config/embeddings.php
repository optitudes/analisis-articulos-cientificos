<?php

return [
    // The small document vectors are generated offline and shipped with the application.
    'artifact_path' => env('TEXT_EMBEDDINGS_PATH', public_path('embeddings/article-vectors.json')),
];
