<?php

namespace App\Wicrew\PageBundle\Controller;

use App\Wicrew\PageBundle\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PageController
 */
class PageController extends Controller implements PageContentControllerInterface {

    /**
     * Page
     *
     * @param Request $request
     *
     * @return Response
     */
    public function homepageAction(Request $request) {
        return $this->render('WicrewPageBundle::homepage.html.twig', []);
    }

    /**
     * {@inheritDoc}
     */
    public function viewPageDetailAction(Request $request, $slug): Response {
        $em = $this->getDoctrine()->getManager();

        if (!$slug) {
            throw $this->createNotFoundException('This page does not exist');
        }

        $page = $em->getRepository(Page::class)->findOneBy(['enabled' => true, 'slug' => $slug]);
        if (!$page) {
            throw $this->createNotFoundException('This page does not exist');
        }

        $utilsService = $this->container->get('wicrew.core.utils');
        if ($utilsService->isBundleExist('WicrewBlockBundle')) {
            $blockService = $this->container->get('wicrew.block.service.block');
            $contentWithBlocks = $blockService->injectBlocks($page->getPageContent());
            $page->setPageContent($contentWithBlocks);
        }

        return $this->render('WicrewPageBundle::detail.html.twig', [
            'page' => $page
        ]);

    }

}
