<?php

namespace App\Wicrew\BlockBundle\Service;

use App\Wicrew\BlockBundle\Entity\Block as BlockEntity;
use App\Wicrew\CoreBundle\Service\Utils;

/**
 * Block
 */
class Block {

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
     * @return Block
     */
    public function setUtils(Utils $utils): Block {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Inject blocks
     *
     * @param string $content
     *
     * @return string
     */
    public function injectBlocks($content) {
        preg_match_all('/\{\{( block [^}]+ )\}\}/', $content, $matches);
        if (is_array($matches) && isset($matches[0])) {
            $foundBlocks = $matches[0];
            foreach ($foundBlocks as $block) {
                $blockAttributes = str_replace(['{{ block ', ' }}'], '', $block);
                $blockAttributes = explode(' ', $blockAttributes);

                $blockIdentifier = '';

                foreach ($blockAttributes as $attribute) {
                    list($key, $value) = explode('=', $attribute);
                    if ($key == 'identifier') {
                        $blockIdentifier = trim(trim($value, '"'), '&quot;');
                        break;
                    }
                }

                if ($blockIdentifier) {
                    $blockContent = $this->loadBlockContent($blockIdentifier);
                    if ($blockContent) {
                        $content = str_replace($block, $blockContent, $content);
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Load block content by identifier
     *
     * @param string $identifier
     *
     * @return string
     */
    public function loadBlockContent($identifier) {
        $content = '';

        $em = $this->getUtils()->getEntityManager();
        $blockObj = $em->getRepository(BlockEntity::class)->findOneBy(['identifier' => $identifier]);
        if ($blockObj) {
            if ($blockObj->getType() == BlockEntity::TYPE_CONTENT) {
                $content = $blockObj->getContent();
            } else if ($blockObj->getType() == BlockEntity::TYPE_TEMPLATE) {
                $content = $this->getUtils()->getContainer()->get('twig')->render($blockObj->getCustomTemplate(), []);
            }
        }

        return $content;
    }

}
