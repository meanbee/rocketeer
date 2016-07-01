<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rocketeer\Container;
use Rocketeer\RocketeerServiceProvider;

require 'vendor/autoload.php';

$container = new Container();
$provider = new RocketeerServiceProvider();
$provider->setContainer($container);
$provider->register();

$container->get('config.publisher')->publish(__DIR__.'/../src/config', 'php');
exec('composer lint');
