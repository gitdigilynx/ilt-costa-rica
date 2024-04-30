<?php

namespace App\Wicrew\ProductBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Wicrew\CoreBundle\Service\SimpleXLSX\SimpleXLSX;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use App\Wicrew\SaleBundle\Entity\Tax;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;

class convertClientXlsxToCsv extends Command
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
            ->setName('convert:xlsx:csv')
            ->setDescription('It will convert client given xslx into import format csv');
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
        $io                 = new SymfonyStyle($input, $output);
        $xlsx               = new SimpleXLSX(__DIR__ . '/ilt_price_list.xlsx');
        $csvFile            = fopen(__DIR__ . '/iltPriceList.csv', 'w');
        $csvHeaderArray     = array(
            "Transportation type",
            "Area from",
            "Area to",
            "Vehicle Type",
            "Departure time",
            "km",
            "Duration",
            "Note",
            "Enabled",
            "Vehicle rack price",
            "Vehicle net price",
            "Adult rack price",
            "Adult net price",
            "Kid rack price",
            "Kid net price",
            "Tax",
            "Addons",
            "Extras",
            "Regular time range enabled",
            "Range start Regular time range",
            "Range end Regular time range",
            "Rack price Regular time range",
            "Net price Regular time range",
            "Tax Regular time range",
            "Flight pick-up time range enabled",
            "Range start Flight pick-up time range",
            "Range end Flight pick-up time range",
            "Rack price Flight pick-up time range",
            "Net price Flight pick-up time range",
            "Tax Flight pick-up time range",
            "Flight drop-off time range enabled",
            "Range start Flight drop-off time range",
            "Range end Flight drop-off time range",
            "Rack price Flight drop-off time range",
            "Net price Flight drop-off time range",
            "Tax Flight drop-off time range",
        );
        fputcsv($csvFile,  $csvHeaderArray);

        if ($xlsx->success()) {
            
            foreach( $xlsx->sheetNames() as $sheetIndex => $sheetName ){
                foreach ($xlsx->rows( $sheetIndex ) as $key => $row) { 
                    if( $key > 4 ){ // SKIP STARTING ROWS FROM SHEET                        
                        $area_to                = $row[0];
                        $kilometers             = $row[1];
                        $hours                  = $row[2];
                        $h1_net_rate            = $row[3];
                        $h1_rack_rate           = $row[4];
                        $hiace_net_rate         = $row[5];
                        $hiace_rack_rate        = $row[6];
                        $sprinter_net_rate      = $row[7];
                        $sprinter_rack_rate     = $row[8];
                        $coaster_net_rate       = $row[9];
                        $coaster_rack_rate      = $row[10];
                        if(empty($area_to)){continue;}
                        for ($x = 1; $x <= 4; $x++) {
                            if( $x == 1 ){
                                $vehicleType        = "Toyota Coaster";
                                $vehicleNetPrice    = $coaster_net_rate;
                                $vehicleRackPrice   = $coaster_rack_rate;
                                
                            }else if( $x == 2 ){
                                $vehicleType        = "Hyundai H-1";
                                $vehicleNetPrice    = $h1_net_rate;
                                $vehicleRackPrice   = $h1_rack_rate;
                            }else if( $x == 3 ){
                                $vehicleType        = "Toyota HIACE";
                                $vehicleNetPrice    = $hiace_net_rate;
                                $vehicleRackPrice   = $hiace_rack_rate;
                            }else if( $x == 4 ){
                                $vehicleType        = "Mercedes Sprinter";
                                $vehicleNetPrice    = $sprinter_net_rate;
                                $vehicleRackPrice   = $sprinter_rack_rate;
                            }

                            $csvDataArray     = array(
                                "Private shuttles",
                                ucwords(strtolower($sheetName)),
                                $area_to,
                                $vehicleType,
                                "",
                                $kilometers,
                                $hours,
                                "",
                                1,
                                $vehicleRackPrice,
                                $vehicleNetPrice,
                                "",
                                "",
                                "",
                                "",
                                0.08,
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                            );
                            fputcsv($csvFile,  $csvDataArray);
                        }


                    }
                }
            }

            $io->success('Finished!');
        } else {
            echo 'xlsx error: ' . $xlsx->error();
        }
        fclose($csvFile);


    }
}
