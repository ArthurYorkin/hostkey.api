<?php

namespace common\services\servers\base;

/**
 * Description of Servers
 * @author ArthurYorkin
 */
class Servers extends ServersDecorator
{
    public function GetListServers() {
        return $this->servers->GetListServers();
    }
}
