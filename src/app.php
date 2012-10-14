<?php

require __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use dflydev\markdown\MarkdownExtraParser;
use Misd\TwigMarkdowner\Twig\Extension\MarkdownerExtension;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
//$app->register(new SilexExtension\GravatarExtension(), array(
//    'gravatar.class_path' => __DIR__ . '/vendor/gravatar-php/src',
//    'gravatar.cache_dir'  => sys_get_temp_dir() . '/gravatar',
//    'gravatar.cache_ttl'  => 240, // 240 seconds
//    'gravatar.options' => array(
//        'size' => 100,
//        'rating' => Gravatar\Service::RATING_G,
//        'secure' => true,
//        'default'   => Gravatar\Service::DEFAULT_404,
//        'force_default' => true
//    )
//));
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../views'),
//    'twig.options' => array('cache' => __DIR__.'/../cache'),
));
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...
    //$twig->addExtension(new MarkdownExtraParser());
    $parser = new MarkdownExtraParser();
    $twig->addExtension(new MarkdownerExtension($parser));
    return $twig;
}));


//$app['composer.doc_dir'] = __DIR__.'/../vendor/composer/composer/doc';

$app['markdown'] = function () {
    return new MarkdownExtraParser();
};
return $app;
