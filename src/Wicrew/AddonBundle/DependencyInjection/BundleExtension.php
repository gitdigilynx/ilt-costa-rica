<?php

namespace App\Wicrew\AddonBundle\DependencyInjection;

use App\Wicrew\CoreBundle\DependencyInjection\WicrewCoreExtension as BaseExtension;

class BundleExtension extends BaseExtension {

    /**
     * Constructor
     */
    public function __construct() {
        $this->addCustomFormThemes([
            '@WicrewAddon/Admin/form.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return 'wicrew_addon';
    }

}
