<?php

require_once __DIR__ . '/vendor/autoload.php';

use voku\stoppropaganda\StopPropaganda;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

$urls = include __DIR__ . '/src/voku/stoppropaganda/data/url_targets.php';

wait(
    parallelMap($urls, static function ($url) {
        $stopPropaganda = new StopPropaganda([$url]);
        $stopPropaganda->start();
    })
);
