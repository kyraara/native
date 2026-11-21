<?php

$emails = array_filter(array_map(
    'trim',
    explode(',', (string) env('ADMIN_EMAILS', 'admin@nativecuy.id'))
));

return [
    'emails' => array_values($emails),
];
