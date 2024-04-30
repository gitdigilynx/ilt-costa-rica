<?php

namespace App\Wicrew\ProductBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\AddonBundle\Entity\Extra;

class ImportAllExtrasToAllPrivateShuttles extends Command {
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
            ->setName('add:extras:private_shuttles')
            ->setDescription('Add all extras, to all private shuttles.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $io                                 = new SymfonyStyle($input, $output);
        $private_shuttle_transportationtype = $this->em->getRepository(TransportationType::class)->findOneByName('Private shuttles');
        // $shared_shuttle_transportationtype  = $this->em->getRepository(TransportationType::class)->findOneByName('Shared shuttles');
        $shuttles                           = $this->em->getRepository(Product::class)->findBy(['transportationType' => [$private_shuttle_transportationtype]]);
        $extras                             = $this->em->getRepository(Extra::class)->findAll();
        // "Imperial Beer (6 pack),Toddler Car Seat,Infant Car Seat,Booster Car Seat,Champagne,Extra Time: 1 hour"

            
        foreach ( $shuttles as $shuttle ) {
            foreach ( $extras as $extra ) {
                $shuttle->addExtra($extra);
                $io->writeln( $extra->getLabel()." added to shuttle ID: ".$shuttle->getId() );
            }    
            $this->em->persist($shuttle);
        }
        

        $this->em->flush();
        $io->success('All extras added to all shuttles!');


    }
}
