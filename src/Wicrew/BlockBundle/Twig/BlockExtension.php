<?php

namespace App\Wicrew\BlockBundle\Twig;

use App\Wicrew\CoreBundle\Service\Utils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * BlockExtension
 */
class BlockExtension extends AbstractExtension {

    /**
     * Core utility class
     *
     * @var Utils
     */
    private $utils;

    /**
     * Utils
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
    }

    /**
     * Get utils
     *
     * @return Utils
     */
    public function getUtils(): Utils {
        return $this->utils;
    }

    /**
     * Set utils
     *
     * @param Utils $utils
     *
     * @return BlockExtension
     */
    public function setUtils(Utils $utils): BlockExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters() {
        return [
            new TwigFilter('injectBlocks', [$this, 'injectBlocks'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('getBlockContent', [$this, 'getBlockContent'])
        ];
    }

    /**
     * Inject blocks
     *
     * @param string $content
     *
     * @return string
     */
    public function injectBlocks($content) {
        return $this->getUtils()->getContainer()->get('wicrew.block.service.block')->injectBlocks($content);
    }

    /**
     * Get block content
     *
     * @param string $identifier
     *
     * @return string
     */
    public function getBlockContent($identifier) {
        $blockService = $this->getUtils()->getContainer()->get('wicrew.block.service.block');
        return $blockService->loadBlockContent($identifier);
    }

}
