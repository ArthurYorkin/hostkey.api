<?php

namespace common\services\inventory\base;

abstract class InventoryDecorator implements InventoryInterface
{
    protected $inventory;

    public function __construct(InventoryInterface $inventory) {
        $this->inventory = $inventory;
    }
}
