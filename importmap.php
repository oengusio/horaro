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
    'bootswatch/dist/cerulean/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/cosmo/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/cyborg/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/darkly/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/flatly/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/journal/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/litera/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/lumen/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/lux/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/materia/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/minty/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/pulse/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/sandstone/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/simplex/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/sketchy/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/slate/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/solar/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/spacelab/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/superhero/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'bootswatch/dist/united/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'pickadate' => [
        'version' => '5.0.0-alpha.3',
    ],
    'moment' => [
        'version' => '2.30.1',
    ],
    'knockout' => [
        'version' => '3.5.3',
    ],
    'remarkable' => [
        'version' => '2.0.1',
    ],
    'nativesortable' => [
        'version' => '0.1.0',
    ],
];
