<?php

return [
    'default_profile_handle' => env('STATAMIC_AI_CHATBOT_DEFAULT_PROFILE', 'default'),

    'providers' => [
        'text' => [
            'driver' => env('STATAMIC_AI_CHATBOT_TEXT_PROVIDER', 'openai'),
            'model' => env('STATAMIC_AI_CHATBOT_TEXT_MODEL', 'gpt-5-mini'),
        ],
        'text_fallbacks' => (static function (): array {
            $fallbacks = env('STATAMIC_AI_CHATBOT_TEXT_FALLBACKS', []);

            if (is_array($fallbacks)) {
                return $fallbacks;
            }

            if (! is_string($fallbacks) || trim($fallbacks) === '') {
                return [];
            }

            $decoded = json_decode($fallbacks, true);

            return is_array($decoded) ? $decoded : [];
        })(),
        'embeddings' => [
            'driver' => env('STATAMIC_AI_CHATBOT_EMBEDDINGS_PROVIDER', 'openai'),
            'model' => env('STATAMIC_AI_CHATBOT_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
            'dimensions' => (int) env('STATAMIC_AI_CHATBOT_EMBEDDINGS_DIMENSIONS', 1536),
            'enabled' => (bool) env('STATAMIC_AI_CHATBOT_EMBEDDINGS_ENABLED', true),
        ],
        'reranking' => [
            'driver' => env('STATAMIC_AI_CHATBOT_RERANKING_PROVIDER'),
            'model' => env('STATAMIC_AI_CHATBOT_RERANKING_MODEL'),
            'enabled' => (bool) env('STATAMIC_AI_CHATBOT_RERANKING_ENABLED', false),
        ],
    ],

    'retention' => [
        'mode' => env('STATAMIC_AI_CHATBOT_RETENTION_MODE', 'conversations_and_leads'),
        'conversation_days' => (int) env('STATAMIC_AI_CHATBOT_CONVERSATION_RETENTION_DAYS', 90),
        'lead_days' => (int) env('STATAMIC_AI_CHATBOT_LEAD_RETENTION_DAYS', 365),
    ],

    'queue' => [
        'connection' => env('STATAMIC_AI_CHATBOT_QUEUE_CONNECTION'),
        'queue' => env('STATAMIC_AI_CHATBOT_QUEUE_NAME', 'default'),
    ],

    'widget' => [
        'position' => env('STATAMIC_AI_CHATBOT_POSITION', 'bottom-right'),
        'width' => env('STATAMIC_AI_CHATBOT_WIDTH', '26rem'),
        'eyebrow_label' => env('STATAMIC_AI_CHATBOT_EYEBROW_LABEL', 'AesirCloud AI'),
        'launcher_label' => env('STATAMIC_AI_CHATBOT_LAUNCHER_LABEL', 'Chat with us'),
        'welcome_title' => env('STATAMIC_AI_CHATBOT_WELCOME_TITLE', 'How can we help?'),
        'welcome_message' => env('STATAMIC_AI_CHATBOT_WELCOME_MESSAGE', 'Ask a question, browse FAQs, or reach support.'),
        'primary_color' => env('STATAMIC_AI_CHATBOT_PRIMARY_COLOR', '#0f766e'),
        'accent_color' => env('STATAMIC_AI_CHATBOT_ACCENT_COLOR', '#f4a261'),
        'support_hours' => env('STATAMIC_AI_CHATBOT_SUPPORT_HOURS'),
        'privacy_notice' => env('STATAMIC_AI_CHATBOT_PRIVACY_NOTICE'),
        'logo_url' => env('STATAMIC_AI_CHATBOT_LOGO_URL'),
    ],

    'knowledge' => [
        'max_chunks' => (int) env('STATAMIC_AI_CHATBOT_MAX_CHUNKS', 6),
        'max_chunk_characters' => (int) env('STATAMIC_AI_CHATBOT_MAX_CHUNK_CHARACTERS', 1200),
        'chunk_overlap_characters' => (int) env('STATAMIC_AI_CHATBOT_CHUNK_OVERLAP_CHARACTERS', 150),
        'min_similarity' => (float) env('STATAMIC_AI_CHATBOT_MIN_SIMILARITY', 0.28),
        'rerank_top_n' => (int) env('STATAMIC_AI_CHATBOT_RERANK_TOP_N', 5),
    ],

    'lead_destinations' => [
        'database' => true,
        'email' => [
            'enabled' => (bool) env('STATAMIC_AI_CHATBOT_LEAD_EMAIL_ENABLED', false),
            'to' => env('STATAMIC_AI_CHATBOT_LEAD_EMAIL_TO'),
        ],
        'webhook' => [
            'enabled' => (bool) env('STATAMIC_AI_CHATBOT_LEAD_WEBHOOK_ENABLED', false),
            'url' => env('STATAMIC_AI_CHATBOT_LEAD_WEBHOOK_URL'),
            'secret' => env('STATAMIC_AI_CHATBOT_LEAD_WEBHOOK_SECRET'),
        ],
    ],

    'youtube' => [
        'enabled' => (bool) env('STATAMIC_AI_CHATBOT_YOUTUBE_ENABLED', true),
        'timeout' => (int) env('STATAMIC_AI_CHATBOT_YOUTUBE_TIMEOUT', 15),
    ],
];
