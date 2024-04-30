<?php

namespace App\Wicrew\CoreBundle\DependencyInjection;

use Sensio\Bundle\FrameworkExtraBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;

class WicrewCoreExtension extends BaseExtension implements PrependExtensionInterface {

    protected static $customFormThemes = [
        '@EasyAdmin/form/bootstrap_4.html.twig',
        '@FOSCKEditor/Form/ckeditor_widget.html.twig'
    ];

    /**
     * Add a custom form theme
     *
     * @param string $theme
     */
    public function addCustomFormThemes(array $themes) {
        foreach ($themes as $theme) {
            self::$customFormThemes[] = $theme;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container) {
        // Get all bundles
        $bundles = $container->getParameter('kernel.bundles');
        // Determine if EasyAdminBundle is registered
        if (
            isset($bundles['EasyAdminBundle'])
            && isset(self::$customFormThemes)
            && is_array(self::$customFormThemes)
            && count(self::$customFormThemes) > 0
        ) {
            $container->prependExtensionConfig('easy_admin', [
                'design' => [
                    'form_theme' => self::$customFormThemes
                ]
            ]);

            //            foreach ($container->getExtensions() as $name => $extension) {
            //                switch ($name) {
            //                    case 'easy_admin':
            //                        $container->prependExtensionConfig($name, [
            //                            'design' => [
            //                                'form_theme' => $formThemes
            //                            ]
            //                        ]);
            //                        break;
            //                }
            //            }
        }

        // Process the configuration
        $configs = $container->getExtensionConfig($this->getAlias());

        // Use the Configuration class to generate a config array
        $config = $this->processConfiguration(new Configuration(), $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return 'wicrew_core';
    }

}