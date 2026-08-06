<?php

$bootswatchThemes = [
    'cerulean',
    'cosmo',
    'cyborg',
    'darkly',
    'flatly',
    'journal',
    'litera',
    'lumen',
    'lux',
    'materia',
    'minty',
    'pulse',
    'sandstone',
    'simplex',
    'sketchy',
    'slate',
    'solar',
    'spacelab',
    'superhero',
    'united',
    'yeti',
];
$themesResult = [];

foreach ($bootswatchThemes as $theme) {
    $themesResult["bootswatch/dist/$theme/bootstrap.min.css"] = [
        'version' => '4.6.2',
        'type' => 'css',
    ];
}

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.23',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    '@fortawesome/fontawesome-free/js/all.min.js' => [
        'version' => '7.2.0',
    ],
    '@fortawesome/fontawesome-free/css/fontawesome.min.css' => [
        'version' => '7.2.0',
        'type' => 'css',
    ],
    'bootstrap' => [
        'version' => '4.6.2',
    ],
    'jquery' => [
        'version' => '3.6.0',
    ],
    'popper.js' => [
        'version' => '1.16.1',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/yeti/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    ...$themesResult,
];
