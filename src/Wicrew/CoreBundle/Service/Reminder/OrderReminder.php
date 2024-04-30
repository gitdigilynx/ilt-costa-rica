<?php

namespace App\Wicrew\CoreBundle\Service\Reminder;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\SaleBundle\Entity\OrderItem;

/**
 * OrderReminder
 */
class OrderReminder extends ReminderAbstract {

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
    }

    /**
     * On action
     * Trip Advisor
     *
     * @return bool
     * @throws \Exception
     */
    public function tripAdvisor() {
        $now = new \DateTime();
        $translator = $this->getUtils()->getTranslator();
        $em = $this->getUtils()->getEntityManager();
        $OrderItems = $em->getRepository(OrderItem::class)->createQueryBuilder('oit')
            ->where("oit.pickUpDate < (:nowdate) AND oit.tripAdvisorReminder != 1 AND oit.parent IS NULL")
            ->setParameter('nowdate', $now->format('Y-m-d'))
            ->getQuery()
            ->getResult();
        $mailer = $this->getUtils()->getContainer()->get('wicrew.core.mailer');
        $subject = $translator->trans('sale.tripadvisor.reminder');
        foreach ($OrderItems as $OrderItem) {
            $sevenDayNext = strtotime($OrderItem->getPickUpDate()->format('Y-m-d'));
            $sevenDayNextFormat = date('Y-m-d', strtotime("+7 day", $sevenDayNext));
            if (strtotime($now->format('Y-m-d')) >= strtotime("+7 day", $sevenDayNext)) {

                $body = $this->getUtils()->getContainer()->get('templating')->render('WicrewSaleBundle:Email:tripadvisor.reminder.html.twig', [
                    'orderitem' => $OrderItem
                ]);

                $mailer->send([
                    'from' => $this->getUtils()->getContainer()->getParameter('system_email'),
                    'to' => $OrderItem->getOrder()->getEmail(),
                    'replyTo' => $this->getUtils()->getContainer()->getParameter('system_email'),
                    'subject' => $subject,
                    'body' => $body
                ]);

                $OrderItem->setTripAdvisorReminder(1);
                $em->persist($OrderItem);
                return true;
            }
        }

        return true;
    }
}
