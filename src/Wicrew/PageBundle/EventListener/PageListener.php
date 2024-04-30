<?php

namespace App\Wicrew\PageBundle\EventListener;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PageBundle\Controller\PageContentControllerInterface;
use App\Wicrew\PageBundle\Entity\PageContent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PageListener {

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

    /**
     * RequestStack
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Constructor
     *
     * @param RequestStack $requestStack
     */
    public function __construct(Utils $utils, RequestStack $requestStack) {
        $this->setUtils($utils)
            ->setRequestStack($requestStack);
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
     * @return PageListener
     */
    public function setUtils(Utils $utils): PageListener {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get request stack
     *
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack {
        return $this->requestStack;
    }

    /**
     * Set request stack
     *
     * @param RequestStack $requestStack
     *
     * @return PageListener
     */
    public function setRequestStack(RequestStack $requestStack): PageListener {
        $this->requestStack = $requestStack;
        return $this;
    }

    /**
     * On kernel exception
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
    }

}
