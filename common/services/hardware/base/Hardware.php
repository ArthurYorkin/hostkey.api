<?php

namespace common\services\hardware\base;

/**
 * Description of Hardware
 * @author ArthurYorkin
 */
class Hardware extends HardwareDecorator
{
    public function DisableEnablePort() {
        return $this->hardware->DisableEnablePort();
    }
    
    public function ChangeSpeed() {
        return $this->hardware->ChangeSpeed();
    }
    
    public function ChangeVlan() {
        return $this->hardware->ChangeVlan();
    }
    
    public function setPortSecurity() {
        return $this->hardware->setPortSecurity();
    }
    
    public function ShowVlan() {
        return $this->hardware->ShowVlan();
    }
    
    public function showPortSecurity() {
        return $this->hardware->showPortSecurity();
    }
    
    public function showDataSSH() {
        return $this->hardware->showDataSSH();
    }
}
