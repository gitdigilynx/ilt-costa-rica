<?php

namespace App\Wicrew\CronBundle\Controller;

use App\Wicrew\CoreBundle\Service\Reminder;
use App\Wicrew\CronBundle\Entity\Cron;
use App\Wicrew\CronBundle\Entity\CronLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * DefaultController
 */
class DefaultController extends BaseController {

    /**
     * Alert prefix code
     */
    const ALERT_PREFIX_CODE = 'alert.';

    /**
     * Processing cron
     *
     * @Route("/", name="wicrew_cron_core")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function processAction(Request $request) {
        $result = [
            'status' => 'success',
            'message' => ''
        ];

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(Cron::class);

        $crons = $repository->getExecutableCrons();
        foreach ($crons as $cron) {
            $lastLog = $cron->getLogs()->first();
            $allowProcessing =
                // No last executed found
                !$lastLog
                // Allow process if the last executed is success
                || $lastLog->getStatus() == CronLog::STATUS_SUCCESS
                // Allow process even the last executed is processing
                || ($lastLog->getStatus() == CronLog::STATUS_PROCESSING && $cron->isKeepExecutingWhenProcessing())
                // Allow process even the last executed is error
                || ($lastLog->getStatus() == CronLog::STATUS_ERROR && $cron->isKeepExecutingWhenError());

            // Process the cron
            if ($allowProcessing) {
                $em->beginTransaction();
                try {
                    // Keep the log
                    $log = new CronLog();
                    $log->setCron($cron)
                        ->setExecutedAt(new \DateTime())
                        ->setStatus(CronLog::STATUS_PROCESSING);
                    $em->persist($log);

                    if (substr($cron->getCode(), 0, strlen(self::ALERT_PREFIX_CODE)) == self::ALERT_PREFIX_CODE) {
                        $service = $this->container->get('wicrew.core.reminder');

                        list($prefix, $type) = explode('.', $cron->getCode());
                        switch ($type) {
                            case 'order':
                                $type = Reminder::TYPE_ORDER;
                                break;
                            default:
                                throw new \Exception('Cound not find the reminder type.');
                        }

                        $result = $service->trigger($type, $cron->getAction(), $cron->getParameters());
                    } else {
                        $service = $this->container->get($cron->getService());
                        $action = $cron->getAction();
                        $parameters = $cron->getParameters();

                        // Execute the service's action
                        if (method_exists($service, $action)) {
                            $result = $service->$action($parameters);
                        } else {
                            throw new \Exception('Action "' . $action . '" not found');
                        }
                    }

                    $log->setFinishedAt(new \DateTime());

                    // Check the result
                    if (!$result['message'] && isset($result['status']) && $result['status'] == 'success') {
                        $result['status'] = 'success';

                        $log->setStatus(CronLog::STATUS_SUCCESS);
                    } else {
                        $result['status'] = 'failed';

                        $log->setStatus(CronLog::STATUS_ERROR);
                        $this->sendReportEmail($cron, $log);
                    }
                } catch (\Exception $ex) {
                    $message = $ex->getMessage();

                    $result['status'] = 'failed';
                    $result['message'] = $message;

                    $log->setFinishedAt(new \DateTime());
                    $log->setStatus(CronLog::STATUS_ERROR);
                    $log->setMessage($message);

                    $this->sendReportEmail($cron, $log);
                }

                if (\Cron\CronExpression::isValidExpression($cron->getExpression())) {
                    $nextExecution = \Cron\CronExpression::factory($cron->getExpression())->getNextRunDate();
                    $cron->setExecutedAt($nextExecution);
                    $em->persist($cron);
                } else {
                    $result['status'] = 'failed';
                    $result['message'] = 'Expression "' . $cron->getExpression() . '" of cron "' . $cron->getTitle() . '" is invalid';
                }

                $em->commit();
                $em->flush();
                $em->clear();
            }
        }
        return $this->json($result, 200);
    }

    /**
     * Send report email
     *
     * @param Cron $cron
     * @param CronLog $log
     */
    private function sendReportEmail(Cron $cron, CronLog $log) {
        $mailService = $this->container->get('wicrew.core.mailer');

        $mailService->send([
            'from' => $this->container->getParameter('system_email'),
            'to' => $this->container->getParameter('system_email'),
            'replyTo' => $this->container->getParameter('system_email'),
            'subject' => 'Cron "' . $cron->getTitle() . '" is failed',
            'body' => 'Log ID "' . $log->getId() . '", message = "' . $log->getMessage() . '"'
        ]);

        return true;
    }

}
