<?php

namespace App\Wicrew\AdditionalFeeBundle\DependencyInjection;

use App\Wicrew\CoreBundle\DependencyInjection\WicrewCoreExtension as BaseExtension;

class BundleExtension extends BaseExtension {

    /**
     * Constructor
     */
    public function __construct() {
        $this->addCustomFormThemes([
            '@WicrewAdditionalFee/Admin/Form/google-place.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return 'wicrew_additional_fee';
    }

}
