<?php

namespace App\Wicrew\ActivityBundle\DependencyInjection;

use App\Wicrew\CoreBundle\DependencyInjection\WicrewCoreExtension as BaseExtension;

class BundleExtension extends BaseExtension {

    /**
     * Constructor
     */
    public function __construct() {
        $this->addCustomFormThemes([
            '@WicrewActivity/Admin/form.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return 'wicrew_activity';
    }

}
