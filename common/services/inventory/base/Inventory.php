<?php

namespace common\services\inventory\base;

/**
 * Description of inventory
 * @author ArthurYorkin
 */
class Inventory extends InventoryDecorator
{
    public function GetEquip() {
        return $this->inventory->GetEquip();
    }
    
    public function GetNetwork() {
        return $this->inventory->GetNetwork();
    }
    
    public function GetClient() {
        return $this->inventory->GetClient();
    }
    
    public function GetAll() {
        return $this->inventory->getAll();
    }

    public function validParam() {
        return $this->inventory->validParam();
    }

    public function GetListServers() {
        return $this->inventory->GetListServers();
    }
}
