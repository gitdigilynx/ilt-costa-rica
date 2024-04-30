<?php

namespace App\Wicrew\SaleBundle;

use App\Wicrew\SaleBundle\DependencyInjection\BundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WicrewSaleBundle extends Bundle {
    public function getContainerExtension() {
        return new BundleExtension();
    }
}