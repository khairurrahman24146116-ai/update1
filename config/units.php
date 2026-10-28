<?php

return [
    'enabled' => array_values(array_filter(array_map(
        fn ($code): string => strtolower(trim((string) $code)),
        explode(',', (string) env('ENABLED_UNITS', 'lpi,sma')),
    ))),
];
