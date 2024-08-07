<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Spiral\RoadRunner;
use Spiral\RoadRunner\Http\PSR7Worker;

include "vendor/autoload.php";

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr17Factory();

$worker = new PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($req = $worker->waitRequest()) {
    try {
        $rsp = new Response();
        $rsp->getBody()->write('test');

        $worker->respond($rsp);
    } catch (Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
