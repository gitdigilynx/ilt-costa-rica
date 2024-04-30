<?php

namespace App\Wicrew\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * IndexController
 */
class IndexController extends Controller {

    /**
     * Clean too big log files
     *
     * @Route(path = "clean-log", name = "cron_clean_log")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cleanLogAction(Request $request) {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        $fileSystem = new Filesystem();

        $logDir = $this->container->get('kernel')->getLogDir();

        $finder = new Finder();
        $finder->name('*.log')->size('> 32M');

        foreach ($finder->in($logDir) as $file) {
            try {
                $fileSystem->remove($logDir . DIRECTORY_SEPARATOR . $file->getFileName());
            } catch (IOExceptionInterface $ex) {
                $response['status'] = 'failed';
                $response['message'] = $ex->getMessage();
                break;
            }
        }

        return new JsonResponse($response);
    }

}
