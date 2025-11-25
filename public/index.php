<?php

//Front Controller: tiny bootstrap. Point your vhst to public/

require_once __DIR__ . '/../core/bootstrap.php';
use App\Core\Router;

$router = new Router(__DIR__ . '/../app');

//Enable-Disable Cache
$router->setCache(true)

//Example middleware: attach timer and simple logger
$router->use(function($req,$next){

})

