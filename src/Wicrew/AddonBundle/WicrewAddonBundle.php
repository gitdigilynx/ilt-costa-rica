<?php

namespace App\Wicrew\AddonBundle;

use App\Wicrew\AddonBundle\DependencyInjection\BundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WicrewAddonBundle extends Bundle {

    /**
     * {@inheritDoc}
     */
    public function getContainerExtension() {
        return new BundleExtension();
    }

}
