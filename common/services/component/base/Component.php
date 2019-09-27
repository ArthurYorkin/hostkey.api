<?php

namespace common\services\component\base;

/**
 * Description of Component
 * @author ArthurYorkin
 */
class Component extends ComponentDecorator
{
    public function GetComponents() {
        return $this->component->GetComponents();
    }
}
