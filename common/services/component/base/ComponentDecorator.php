<?php

namespace common\services\component\base;

abstract class ComponentDecorator implements ComponentInterface
{
    protected $component;

    public function __construct(ComponentInterface $component) {
        $this->component = $component;
    }
}
