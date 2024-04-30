<?php

namespace App\Wicrew\SaleBundle\Controller;

use App\Wicrew\CoreBundle\Controller\Controller as Controller;
use App\Wicrew\SaleBundle\Entity\Order;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends Controller {
    /**
     * Transportation management save
     *
     * @Route(
     *     path = "order/review_emails",
     *     name = "order_review_emails_cron",
     *     methods = { "GET" }
     *     )
     *
     * @throws Exception
     * @noinspection PhpMethodParametersCountMismatchInspection
     */
    public function driverEmailAction() {
        /* @var Order[] $orders */
        $orders = $this->getEM()->getRepository(Order::class)->createQueryBuilder('orderEntity')
            ->where('orderEntity.feedbackEmailSent = FALSE')
            ->andWhere('orderEntity.status = :paidStatus')
            ->setParameter('paidStatus', Order::STATUS_PAID)
            ->getQuery()
            ->getResult();

        $now = new DateTime();

        /* @var Order[] $ordersSent */
        $ordersSent = [];
        foreach ($orders as $order) {
            $allOrdersFulfilled = true;
            foreach ($order->getItems() as $orderItem) {
                $dropDateTime = $orderItem->getPickDateAndTime();
                $allOrdersFulfilled = $dropDateTime !== null;
                if ($allOrdersFulfilled) {
                    $diff = $now->diff($dropDateTime);
                    $allOrdersFulfilled = $diff->days >= 1;
                }

                if (!$allOrdersFulfilled) {
                    break;
                }
            }

            if ($allOrdersFulfilled) {
                $ordersSent[] = $order;
            }
        }

        foreach ($ordersSent as $order) {
            $coreUtils = $this->get('wicrew.core.utils');

            $mailer = $this->get('wicrew.core.mailer');

            $from = $coreUtils->getContainer()->getParameter('system_email');
            $to = $order->getEmail();
            $subject = $this->translator()->trans('email.review.subject');
            $emailContent = $this->renderTwigToString('@WicrewSale/Email/email.review_us.html.twig', [
                'order' => $order
            ]);

            $mailer->send([
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'body' => $emailContent
            ]);

            $order->setFeedbackEmailSent(true);
        }

        if (count($ordersSent) > 0) {
            $this->getEM()->flush();
            return new Response('Banana sunday.');
        } else {
            return new Response('Nothing here buds.' . $now->format('M d Y H:i:s'));
        }
    }
}
