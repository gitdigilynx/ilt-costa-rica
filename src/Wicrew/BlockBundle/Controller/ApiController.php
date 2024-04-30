<?php

namespace App\Wicrew\BlockBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

//use Symfony\Contracts\Translation\TranslatorInterface;
//use Symfony\Component\HttpFoundation\Response;

/**
 * ApiController
 */
class ApiController extends Controller {

    /**
     * Get blocks in JSON
     *
     * @Route(path = "blocks", name = "wicrew_api_blocks")
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function blocksAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $blockEntities = $em->getRepository(\App\Wicrew\BlockBundle\Entity\Block::class)->findBy([], ['title' => 'ASC']);
        $blocks = [];
        foreach ($blockEntities as $block) {
            $blocks[] = [$block->getTitle(), $block->getIdentifier()];
        }

        return $this->json($blocks);
    }

}
