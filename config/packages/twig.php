<?php

// config/packages/twig.php
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Config\TwigConfig;

return static function (TwigConfig $twig): void {
    $twig->strictVariables(true);

    $twig->global('utils')->value(service('App\Twig\TwigUtils'));

    $twig->global('oauth_settings')->value('%oauth%');
};
