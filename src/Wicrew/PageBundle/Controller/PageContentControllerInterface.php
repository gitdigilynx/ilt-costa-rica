<?php

namespace App\Wicrew\PageBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PageContentControllerInterface
 */
interface PageContentControllerInterface {

    /**
     * View page detail
     *
     * @param Request $request
     * @param string $slug
     *
     * @return Response
     */
    public function viewPageDetailAction(Request $request, $slug): Response;

}
