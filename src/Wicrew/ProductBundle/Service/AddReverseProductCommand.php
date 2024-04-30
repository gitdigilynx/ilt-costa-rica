<?php

namespace App\Wicrew\ProductBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Wicrew\ProductBundle\Entity\Product;

class AddReverseProductCommand extends Command
{
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
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    /**
     * Configure
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('duplicate:reverse:product')
            ->setDescription('This command will add a new product for reverse areas, if not exists already.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io              = new SymfonyStyle($input, $output);
        $products        = $this->em->getRepository(Product::class)->findBy(
            [
                'enabled'               => 1,
                'archived'              => 0,
            ]
        );
        $products_count  = count($products);
        $io->writeln('Total Products #' . $products_count);

        foreach ($products as $product) {
            
            $transportationType  = $product->getTransportationType();
            $area_from           = $product->getAreaFrom();
            $area_to             = $product->getAreaTo();
            $vehicule_type       = $product->getVehicleType();
         
            $reverse_products    = $this->em->getRepository(Product::class)->findBy(
                [
                    'enabled'               => 1,
                    'archived'              => 0,
                    'transportationType'    => $transportationType,
                    'areaFrom'              => $area_to,
                    'areaTo'                => $area_from,
                    'vehicleType'           => $vehicule_type,
                ]
            );

            $reverse_products_count = count($reverse_products); 
            if($reverse_products_count == 0){
                $newProduct = clone $product;
                $newProduct->setAreaFrom($area_to);
                $newProduct->setAreaTo($area_from);
                $this->em->persist($newProduct);
                $io->writeln('New Product Added');

            }
        }

        $this->em->flush();
        $io->success('Finished!');

    }
}
