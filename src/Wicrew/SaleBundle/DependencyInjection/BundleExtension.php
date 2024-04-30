<?php

namespace App\Wicrew\SaleBundle\DependencyInjection;

use App\Wicrew\CoreBundle\DependencyInjection\WicrewCoreExtension as BaseExtension;

class BundleExtension extends BaseExtension {

    /**
     * Constructor
     */
    public function __construct() {
        $this->addCustomFormThemes([
            '@WicrewSale/Admin/Form/order.detail.html.twig',
            '@WicrewSale/Admin/Form/order.items.html.twig',
            '@WicrewSale/Admin/Form/order.history.html.twig',
            '@WicrewSale/Admin/Form/order.mail.sending.html.twig'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return 'wicrew_sale';
    }

}
