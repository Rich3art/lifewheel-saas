<?php

return [
    'path' => base_path('plugins'),
    'max_upload_megabytes' => env('PLUGIN_UPLOAD_MAX_MB', 10),
    'core_version' => env('LIFEWHEEL_CORE_VERSION', '0.4.0'),
];
