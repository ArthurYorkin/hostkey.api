<?php

namespace common\services\servers\base;

abstract class ServersDecorator implements ServersInterface
{
    protected $servers;

    public function __construct(ServersInterface $servers) {
        $this->servers = $servers;
    }
}
