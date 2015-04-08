<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));


//$loader->add('Inlead\Easyscreen\SearchBundle\Controller', __DIR__ . '/../src/Inlead/Easyscreen/SearchBundle/TingClient.php');

return $loader;
