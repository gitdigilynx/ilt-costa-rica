<?php

namespace App\Wicrew\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * NotifyController
 */
class NotifyController extends BaseController {

    /**
     * Notify alerts action
     *
     * @Route(path="alerts", name="cron_notify_alert")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function alertsAction(Request $request) {

        $translator = $this->get('translator');
        $reminder = $this->container->get('wicrew.core.reminder');

        echo "Process End";

        return $this->json([]);
    }

}
