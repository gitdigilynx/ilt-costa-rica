<?php

namespace App\Wicrew\CoreBundle\Service;

use App\Wicrew\CoreBundle\Entity\MailStack;
use App\Wicrew\CoreBundle\Entity\MailStackBody;
use Swift_Mailer;

/**
 * Mailer
 */
class Mailer {

    /**
     * Config key path
     */
    const CONFIG_KEY = 'general/smtp';

    /**
     * Utils
     *
     * @var Utils
     */
    private $utils;

    /**
     * Options
     *
     * @var array
     */
    private $options = [];

    /**
     * Constructor
     *
     * @param Utils $utils
     */
    public function __construct(Utils $utils) {
        $this->setUtils($utils);
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
     * @return Mailer
     */
    public function setUtils(Utils $utils): Mailer {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Set options
     *
     * @param array $options
     *
     * @return Mailer
     */
    public function setOptions(array $options): Mailer {
        $this->options = $options;
        return $this;
    }

    /**
     * Get mailer
     *
     * @return Swift_Mailer
     */
    public function getMailer(): Swift_Mailer {
        $container = $this->getUtils()->getContainer();
        $transport = new \Swift_SmtpTransport();

        $transport->setHost($container->getParameter('smtp_mail_host'))
            ->setPort($container->getParameter('smtp_mail_port'))
            ->setUsername($_ENV['SENDGRID_USERNAME'])
            ->setPassword($_ENV['SENDGRID_API_KEY'])
            ->setAuthMode('login');

        return new Swift_Mailer($transport);
    }

    /**
     * Send emails
     *
     * @return array
     */
    public function sendEmails() {
        $response = [
            'status' => 'success',
            'message' => ''
        ];

        $em = $this->getUtils()->getEntityManager();
        $mailStackRepository = $em->getRepository(MailStack::class);
        $mails = $mailStackRepository->getMailsToSend();

        try {
            foreach ($mails as $mail) {
                $result = $this->send([
                    'from' => $mail->getSender(),
                    'to' => $mail->getRecipient(),
                    'replyTo' => $mail->getReply(),
                    'subject' => $mail->getSubject(),
                    'body' => $mail->getBody()->getContent(),
                    'attachments' => $mail->getAttachments()
                ]);

                if (is_numeric($result) && $result > 0) {
                    $mail->setSent(true);
                    $em->persist($mail);
                    $em->flush();
                }
            }
        } catch (\Exception $ex) {
            $response['status'] = 'error';
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Send email
     *
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function send(array $data) {
        if (
            !isset($data['from'])
            || !isset($data['to'])
            || !isset($data['subject'])
            || !isset($data['body'])
        ) {
            throw new \Exception('A required parameter is missing ("from", "to", "subject" or "body")');
        }

        if (!filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('"' . $data['to'] . '" is invalid email');
        }

        $contentType = isset($data['contentType']) ? $data['contentType'] : 'text/html; charset=UTF-8';
        $from = $data['from'];
        $to = $data['to'];
        $sender = isset($data['sender']) ? $data['sender'] : null;
        $replyTo = isset($data['replyTo']) ? $data['replyTo'] : null;
        $cc = isset($data['cc']) ? $data['cc'] : null;
        $bcc = isset($data['bcc']) ? $data['bcc'] : null;

        $subject = $data['subject'];
        $body = $data['body'];
        $bodyContentType = isset($data['bodyContentType']) ? $data['bodyContentType'] : 'text/html';
        $bodyCharset = isset($data['bodyCharset']) ? $data['bodyCharset'] : null;

        $part = isset($data['part']) ? $data['part'] : null;
        $partContentType = isset($data['partContentType']) ? $data['partContentType'] : 'text/plain';
        $partCharset = isset($data['partCharset']) ? $data['partCharset'] : null;

        $attachments = isset($data['attachments']) && is_array($data['attachments']) ? $data['attachments'] : [];

        $message = new \Swift_Message();
        $message->setContentType($contentType)
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body, $bodyContentType, $bodyCharset);

        if ($sender) {
            $message->setSender($sender);
        }

        if ($replyTo) {
            $message->setReplyTo($replyTo);
        } else {
            $message->setReplyTo($from);
        }

        if ($cc) {
            if (!is_array($cc)) {
                $cc = [$cc];
            }

            foreach ($cc as $mail) {
                $message->addCc($mail);
            }
        }

        if ($bcc) {
            if (!is_array($bcc)) {
                $bcc = [$bcc];
            }

            foreach ($bcc as $mail) {
                $message->addBcc($mail);
            }
        }

        if ($part) {
            $message->addPart($part, $partContentType, $partCharset);
        }

        foreach ($attachments as $attachment) {
            if (is_array($attachment) && isset($attachment['path']) && is_file($attachment['path']) && is_readable($attachment['path'])) {
                $filePath = $attachment['path'];
                $filename = isset($attachment['filename']) ? $attachment['filename'] : pathinfo($filePath, PATHINFO_BASENAME);
                $message->attach(\Swift_Attachment::fromPath($filePath)->setFilename($filename));
            }
        }

        return $this->getMailer()->send($message);
    }

    /**
     * Add mail queue
     *
     * @param array $mailData
     * @param bool $noFlush
     *
     * @return bool
     */
    public function addQueue(array $mailData, $noFlush = false) {
        try {
            $em = $this->getUtils()->getEntityManager();
            $mailStack = new MailStack();
            $mailStackBody = new MailStackBody();

            if (isset($mailData['lead']) && $mailData['lead']) {
                $mailStack->setLead(
                    $mailData['lead'] instanceof \App\Wicrew\SaleBundle\Entity\Lead
                        ? $mailData['lead']
                        : $em->getReference('\App\Wicrew\SaleBundle\Entity\Lead', $mailData['lead'])
                );
            }

            if (isset($mailData['circuit']) && $mailData['circuit']) {
                $mailStack->setCircuit(
                    $mailData['circuit'] instanceof \App\Wicrew\CircuitBundle\Entity\Circuit
                        ? $mailData['circuit']
                        : $em->getReference('\App\Wicrew\CircuitBundle\Entity\Circuit', $mailData['circuit'])
                );
            }

            if (isset($mailData['presentation']) && $mailData['presentation']) {
                $mailStack->setPresentation($mailData['presentation']);
            }

            if (isset($mailData['order']) && $mailData['order']) {
                $mailStack->setOrder(
                    $mailData['order'] instanceof \App\Wicrew\SaleBundle\Entity\Order
                        ? $mailData['order']
                        : $em->getReference('\App\Wicrew\SaleBundle\Entity\Order', $mailData['order'])
                );
            }

            if (isset($mailData['passenger']) && $mailData['passenger']) {
                $mailStack->setPassenger(
                    $mailData['passenger'] instanceof \App\Wicrew\SaleBundle\Entity\LeadParticipant
                        ? $mailData['passenger']
                        : $em->getReference('\App\Wicrew\SaleBundle\Entity\LeadParticipant', $mailData['passenger'])
                );
            }

            if (isset($mailData['agent']) && $mailData['agent']) {
                $mailStack->setAgent(
                    $mailData['agent'] instanceof \App\Wicrew\AgentBundle\Entity\User
                        ? $mailData['agent']
                        : $em->getReference('\App\Wicrew\AgentBundle\Entity\User', $mailData['agent'])
                );
            }

            if (isset($mailData['customer']) && $mailData['customer']) {
                $mailStack->setCustomer(
                    $mailData['customer'] instanceof \App\Wicrew\CustomerBundle\Entity\Customer
                        ? $mailData['customer']
                        : $em->getReference('\App\Wicrew\CustomerBundle\Entity\Customer', $mailData['customer'])
                );
            }

            $mailStack->setMailCode($mailData['mailCode']);
            $mailStack->setSender($mailData['sender']);
            $mailStack->setRecipient($mailData['recipient']);

            if (isset($mailData['reply']) && $mailData['reply']) {
                $mailStack->setReply($mailData['reply']);
            }

            $mailStack->setSubject($mailData['subject']);
            $mailStack->setSendDate($mailData['sendDate']);

            $mailStackBody->setContent($mailData['content']);

            $mailStack->setBody($mailStackBody);
            $mailStackBody->setHeader($mailStack);

            if (isset($mailData['attachments']) && is_array($mailData['attachments']) && $mailData['attachments']) {
                $mailStack->setAttachments($mailData['attachments']);
            }

            $em->persist($mailStack);
            $em->persist($mailStackBody);
            if (!$noFlush) {
                $em->flush();
            }

            return true;
        } catch (\Exception $ex) {
            echo $ex;
            return false;
        }
    }

}
