<?php

/* Freshful Control Centre update 2 (15 Nov 2026): AI analyst settings. The key is read ONLY here, on the server.
   Add to .env (never in code, never in the browser):
     CC_AI_PROVIDER=anthropic        (anthropic or openai)
     CC_AI_KEY=your-key
     CC_AI_MODEL=claude-sonnet-5-5   (optional)
   With no key the analyst still answers from the numbers (numbers-only mode). */
return [
    'ai_provider' => env('CC_AI_PROVIDER', 'anthropic'),
    'ai_key' => env('CC_AI_KEY', ''),
    'ai_model' => env('CC_AI_MODEL', ''),
    'ai_timeout' => 25,
    'ai_daily_cap' => (int) env('CC_AI_DAILY_CAP', 100),
];
