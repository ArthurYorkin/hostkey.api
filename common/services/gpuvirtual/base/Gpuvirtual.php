<?php

namespace common\services\gpuvirtual\base;

/**
 * Description of GpuVirtual
 * @author ArthurYorkin
 */
class Gpuvirtual extends GpuvirtualDecorator
{
    public function GetListGpuVirtual() {
        return $this->gpuvirtual->GetListGpuVirtual();
    }
}
