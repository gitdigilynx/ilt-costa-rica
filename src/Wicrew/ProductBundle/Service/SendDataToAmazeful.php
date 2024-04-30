<?php

namespace App\Wicrew\ProductBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\Order;
use DateTime;
use App\Wicrew\DateAvailability\Entity\HistoryLog;

class SendDataToAmazeful extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * CsvImportCommand constructor.
     *
     * @param EntityManagerInterface $em
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $em) {
        parent::__construct();

        $this->em = $em;
    }

    /**
     * Configure
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure() {
        $this
            ->setName('amazeful:export')
            ->setDescription('It will check for due date items and will send their customer them to amazeful.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        global $kernel;
        $em             = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $io             = new SymfonyStyle($input, $output);

        $allOrders      = $this->em->getRepository(Order::class)->findBy( [ 'amazefulStatus' => [null, 0] ] );
        $allOrderItems  = [];
        foreach( $allOrders as $order ){
            foreach ( $order->getSortedItemsDesc() as $key => $item ) {
                array_push( $allOrderItems, $item );
                break;
            }      
        }
        $today_date     = new DateTime( );
        $today_date_obj = $today_date;
        $today_date     = $today_date->format("d/m/Y");

        // LOGGING INTO HISTORYLOG
        $historyLog         = new HistoryLog();
        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
        $historyLog->setCreatedAt( $currentDateTime );
        $historyLog->setUser( null );
        $historyLog->setModifications("Cron job has been started to send data to Amazeful!" );
        $em->persist($historyLog);
        // LOGGING INTO HISTORYLOG
        $io->writeln( "Today Date: $today_date" );
        foreach ( $allOrderItems as $order_item ) {

            $io->writeln( "#RJ".$order_item->getOrder()->getId()." Date: ". $order_item->getPickDate()->format("d/m/Y"));
            if( $order_item->getPickDate() < $today_date_obj ){
                $order          = $order_item->getOrder();
                $order_id       = $order->getId();
                $customer_fname = trim( $order->getFirstname() );
                $customer_lname = trim( $order->getLastname() );
                $customer_email = trim( $order->getEmail() );
                $customer_tel   = trim( $order->getTel() );
                
                // SENDING CUSTOMER DATA TO AMAZEFUL THROUGH API 
                $utils                 = $kernel->getContainer()->get('wicrew.core.utils');
                $amazefulApiSettings   = $utils->getSystemConfigValues('amazeful/api', true);
                $amazefulApiSettings   = $amazefulApiSettings['amazeful']['api'];
                
                $io->writeln( "Trying to send customer data to Amazeful for #RJ".$order_item->getOrder()->getId()."...." );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.amazeful.com/v1/contacts?api_token=".$amazefulApiSettings["api_token"]."&business=".$amazefulApiSettings["business_id"]);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                ));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'first_name'    => $customer_fname,
                    'last_name'     => $customer_lname,
                    'email'         => $customer_email,
                    'phone'         => $customer_tel,
                    'tags'          => explode(",", $amazefulApiSettings["tags"]),
                ]));
                
                $response['body']    = curl_exec($ch);
                $response['headers'] = curl_getinfo($ch);
                if ( isset( $response['headers']['http_code'] ) ) {
                    if( $response['headers']['http_code'] == 200 ){
                        $io->writeln( "Customer email '$customer_email' Added to Amazeful." );
                        // LOGGING INTO HISTORYLOG
                        $historyLog         = new HistoryLog();
                        $currentDateTime    = new DateTime('now', new \DateTimeZone('GMT-6')); 
                        $historyLog->setCreatedAt( $currentDateTime );
                        $historyLog->setUser( null );
                        $historyLog->setModifications("#RJ$order_id - Customer email '$customer_email' Added to Amazeful by cron job against pick-up date: ".$order_item->getPickDate()->format("d/m/Y") );
                        $em->persist($historyLog);
                        // LOGGING INTO HISTORYLOG
                        $order_item->setAmazefulStatus(1); // CUSTOMER DATA SENT
                        $order->setAmazefulStatus(1); // CUSTOMER DATA SENT

                    }else if( $response['headers']['http_code'] == 422 ){
                        $io->writeln( "ERROR: Customer email: '$customer_email' or phone: '$customer_tel' Already in Amazeful." );
                        $order_item->setAmazefulStatus(1); // CUSTOMER DATA SENT
                        $order->setAmazefulStatus(1); // CUSTOMER DATA SENT
                    }
                }
                curl_close($ch);
                // SENDING CUSTOMER DATA TO AMAZEFUL THROUGH API 

                $em->persist($order_item);
                $em->persist($order);
            }
        }
        $em->flush();        
        $this->em->flush();
        $io->success('Job Finished!');
    }
}