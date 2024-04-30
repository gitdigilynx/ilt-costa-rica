<?php

namespace App\Wicrew\CoreBundle\ControllerTrait;

use Symfony\Component\HttpFoundation\Response;

Trait ControllerTrait{
    public function get404Response($message = '') {
        return new Response(
            $this->renderView('bundles/TwigBundle/Exception/error404.html.twig', ['message' => $message]),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }
}