<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\HistoryLog;

class HistoryLogController extends AbstractController
{
    /**
     * @Route("admin/history/log", name="history_log")
     */
    public function index(): JsonResponse
    {
  
        return new JsonResponse( "Controller Worked!!" );
        exit;
    }
}
