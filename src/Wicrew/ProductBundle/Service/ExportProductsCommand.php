<?php namespace App\Wicrew\ProductBundle\Service;
require_once(dirname(__DIR__, 4) . "/phpSpreadsheet/vendor/autoload.php");

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use App\Wicrew\SaleBundle\Entity\Tax;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer as Writer;

class ExportProductsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $tax1;
    private $tax2;
    private $tax3;
    private $tax4;

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
            ->setName('xlsx:export:products')
            ->setDescription('This command exports all of the products');
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
        $io                                 = new SymfonyStyle($input, $output);
        
        $private_shuttle    = $this->em->getRepository(TransportationType::class)->findOneByName('Private shuttles');
        $shared_shuttle     = $this->em->getRepository(TransportationType::class)->findOneByName('Shared shuttles');
        $shared_jbj         = $this->em->getRepository(TransportationType::class)->findOneByName('Jeep-Boat-Jeep shared');
        $private_jbj        = $this->em->getRepository(TransportationType::class)->findOneByName('Jeep-Boat-Jeep private');
        $riding_jbj         = $this->em->getRepository(TransportationType::class)->findOneByName('Jeep-Boat-Jeep riding');
        $water_taxi         = $this->em->getRepository(TransportationType::class)->findOneByName('Water Taxi');
        $private_flights    = $this->em->getRepository(TransportationType::class)->findOneByName('Private Flight');
        
        
        $products = $this->em->getRepository(Product::class)->findBy(
            [
                'transportationType' => [
                    $private_shuttle,
                    $shared_shuttle,
                    $shared_jbj,
                    $private_jbj,
                    $riding_jbj,
                    $water_taxi,
                    $private_flights
                ],
                'archived' => 0
            ]
        );

        // $this->em->flush();
        $io->writeln( 'Creating new sheet to export...' );
        $spreadsheet    = new Spreadsheet();
        $sheet          = $spreadsheet->getActiveSheet();
        $rowToInsert    = 1;
       
       
            $headers = array(
                'Product ID',
                'Transportation Type',
                'Area From',
                'Area To',
                'Vehicle Type',
                'Duration (Hrs)',
                'Enabled',
                'Fixed Rack Price',
                'Fixed Net Price',
                'adultRackPrice',
                'childRackPrice',
                'adultNetPrice',
                'childNetPrice',
                'Tax',
                'Addon(s)',
                'Extra(s)',
            );
    
            $sheet->fromArray($headers, NULL, 'A' . $rowToInsert); $rowToInsert++;
            
               
            if( count( $products ) > 0 ){
                foreach ( $products as $product ) {
                    
                    $product_id         = $product->getId();
                    $transportationType = $product->getTransportationType();
                    $areaFrom           = $product->getAreaFrom();
                    $AreaTo             = $product->getAreaTo();
                    $vehicleType        = $product->getVehicleType();
                    $departureTime      = $product->getDepartureTime();
                    $duration           = $product->getDuration();
                    $note               = $product->getNote();
                    $enabled            = $product->getEnabled();
                    $fixedRackPrice     = $product->getFixedRackPrice();
                    $fixedNetPrice      = $product->getFixedNetPrice();
                    $adultRackPrice     = $product->getAdultRackPrice();
                    $childRackPrice     = $product->getChildRackPrice();
                    $adultNetPrice      = $product->getAdultNetPrice();
                    $childNetPrice      = $product->getChildNetPrice();
                    $tax                = $product->getTax();
                    $addons             = $product->getAddons();
                    $extras             = $product->getExtras();

                    $addons_labels = array();
                    foreach( $addons as $addon ){
                        $addon_label = $addon->getLabel();
                        array_push($addons_labels, $addon_label);
                    }
                    $addons_labels_str = implode(', ', $addons_labels);
                    
                    $extras_labels = array();
                    foreach( $extras as $extra ){
                        $extra_label = $extra->getLabel();
                        array_push($extras_labels, $extra_label);
                    }
                    $extras_labels_str = implode(', ', $extras_labels);
                    
                    $product_data = array(
                        'product_id'            => $product_id,
                        'transportationType'    => $transportationType,
                        'areaFrom'              => $areaFrom,
                        'AreaTo'                => $AreaTo,
                        'vehicleType'           => $vehicleType,
                        'duration'              => $duration,
                        'enabled'               => $enabled,
                        'fixedRackPrice'        => $fixedRackPrice,
                        'fixedNetPrice'         => $fixedNetPrice,
                        'adultRackPrice'        => $adultRackPrice,
                        'childRackPrice'        => $childRackPrice,
                        'adultNetPrice'         => $adultNetPrice,
                        'childNetPrice'         => $childNetPrice,
                        'tax'                   => $tax,
                        'addons'                => $addons_labels_str,
                        'extras'                => $extras_labels_str,
                    );
                    
                    $sheet->fromArray($product_data, NULL, 'A' . $rowToInsert); $rowToInsert++;
                    $io->writeln('Inserted product id: '.$product_id);

                }
            }else{
                $io->writeln('No products found!');
            }

            for ($i = 'A'; $i !=  $spreadsheet->getActiveSheet()->getHighestColumn(); $i++) {
                $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
            }

            $writer = new Writer\Xlsx($spreadsheet);
            $now    = new DateTime();
            $now    = $now->format("Y-m-d");
            $writer->save( "exported_products_$now.xlsx" );             
            $io->success(($rowToInsert - 2).' products exported.... Finished!');
    }
}
