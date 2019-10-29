<?php

namespace common\services\gpu\base;

abstract class GpuDecorator implements GpuInterface
{
    protected $gpu;

    public function __construct(GpuInterface $gpu) {
        $this->gpu = $gpu;
    }
}
