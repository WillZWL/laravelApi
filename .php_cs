<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.'/app/Console/Commands')
;

return Symfony\CS\Config\Config::create()
    ->fixers(array('-psr0'))
    ->finder($finder)
;

