<?php

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
        'version' => '8.0.13',
    ],
    /*'bootstrap' => [
        'version' => '5.3.5',
    ],*/
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootswatch/yeti/bootstrap.min.css' => [
        'version' => '3.2.0',
        'type' => 'css',
    ],
    'font-awesome/css/font-awesome.min.css' => [
        'version' => '4.7.0',
        'type' => 'css',
    ],
];
