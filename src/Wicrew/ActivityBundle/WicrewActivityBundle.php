<?php

namespace App\Wicrew\ActivityBundle;

use App\Wicrew\ActivityBundle\DependencyInjection\BundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WicrewActivityBundle extends Bundle {

    /**
     * {@inheritDoc}
     */
    public function getContainerExtension() {
        return new BundleExtension();
    }

}