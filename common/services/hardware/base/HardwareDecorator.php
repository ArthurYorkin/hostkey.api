<?php

namespace common\services\hardware\base;

abstract class HardwareDecorator implements HardwareInterface
{
    protected $hardware;

    public function __construct(HardwareInterface $hardware) {
        $this->hardware = $hardware;
    }
}
