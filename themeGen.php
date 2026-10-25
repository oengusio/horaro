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

return $themesResult;
