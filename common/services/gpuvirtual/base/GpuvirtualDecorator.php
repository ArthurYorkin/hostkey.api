<?php

namespace common\services\gpuvirtual\base;

abstract class GpuvirtualDecorator implements GpuvirtualInterface
{
    protected $gpuvirtual;

    public function __construct(GpuvirtualInterface $gpuvirtual) {
        $this->gpuvirtual = $gpuvirtual;
    }
}
