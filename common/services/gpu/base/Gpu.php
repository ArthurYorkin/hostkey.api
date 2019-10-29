<?php

namespace common\services\gpu\base;

/**
 * Description of Gpu
 * @author ArthurYorkin
 */
class Gpu extends GpuDecorator
{
    public function GetListGpu() {
        return $this->gpu->GetListGpu();
    }
}
