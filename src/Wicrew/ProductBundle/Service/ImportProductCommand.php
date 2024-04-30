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

class ImportProductCommand extends Command
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
            ->setName('xlsx:import:product')
            ->setDescription('Imports the mock xlsx data file');
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
        // $private_shuttle_transportationtype = $this->em->getRepository(TransportationType::class)->findOneByName('Private shuttles');
        // $shared_shuttle_transportationtype  = $this->em->getRepository(TransportationType::class)->findOneByName('Shared shuttles');
        // $shuttles                           = $this->em->getRepository(Product::class)->findBy(['transportationType' => [$private_shuttle_transportationtype, $shared_shuttle_transportationtype]]);

        // $this->em->flush();

        $xlsx = new SimpleXLSX(__DIR__ . '/products.xlsx'); // try...catch
        if ($xlsx->success()) {
            $this->tax1 = $this->em->getRepository(Tax::class)->find(1);
            $this->tax2 = $this->em->getRepository(Tax::class)->find(2);
            $this->tax3 = $this->em->getRepository(Tax::class)->find(3);
            $this->tax4 = $this->em->getRepository(Tax::class)->find(4);
            $allProdExtras = [
                'La Fortuna' => [
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    'San Jose' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
                'San Jose Airport' => [
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Zancudo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Potrero' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Villa Blanca Cloud Forest' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Naturalista' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paraiso Quetzal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manzanillo (Limon)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Linda Vista del Norte' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Pavones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Finca Nueva Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Orosi Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais/Santa Teresa (1 driver option)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    ],
                    'Liberia Airport' => [
                    'Alajuela' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Linda Vista del Norte' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cobano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Finca Nueva Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'San Jose (Downtown)' => [
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Zancudo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Potrero' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Villa Blanca Cloud Forest' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Naturalista' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paraiso Quetzal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manzanillo (Limon)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Linda Vista del Norte' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Pavones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Finca Nueva Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Orosi Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'La Fortuna/Arenal' => [
                    'Alajuela' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Villa Blanca Cloud Forest' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Monteverde' => [
                    'Alajuela' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Villa Blanca Cloud Forest' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
                'Jaco' => [
                    'Alajuela' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Quepos/Manuel Antonio' => [
                    'Alajuela' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Atenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bajos del Toro (El Silencio Lodge)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Bijagua' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Mountain Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cahuita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cartago' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Cocles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Costa Rica Marriott San Jose (Belen)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Curridibat' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Escazu' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Esterillos Este/Oeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Fiesta Resort, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Golfito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Guapiles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda AltaGracia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heredia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Limon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nuevo Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ojochal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Parrita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Paso Canoas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Peace Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Coyote' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Puntarenas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Poas Volcano' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Caldera' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Jimenez' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puerto Viejo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Uva' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Puntarenas Ferry' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Dota' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Gerardo de Rivas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Isidro de El General' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Ramon' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sarapiqui' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sierpe' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Siquirres' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Sixaola' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tarcoles' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tilaran' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tortuguero (La Pavona Dock)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Turrialba' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Dreams Las Mareas' => [
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hotel Linda Vista  ' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Rainforest Hotel' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Papagayo' => [
                    'Four Seasons/Andaz/Planet Hollywood' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Casa Chameleon, Las Catalinas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Pinilla' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'JW Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Las Catalinas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dreams Las Mareas' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Avellanas, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Brasilito, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Flamingo, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Grande, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Negra' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Potrero, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hotel Linda Vista  ' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Rainforest Hotel' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Riu' => [
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Junquillal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tamarindo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Westin Playa Conhcal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'W Costa Rica' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hotel Linda Vista  ' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Rainforest Hotel' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'Tamarindo' => [
                    'Andaz Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Observatory Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Blue River Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Borinquen Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Castillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'El Mangroove Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Four Seasons Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hacienda Guachipelin' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'La Fortuna/Arenal' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Los Suenos Marriott' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Liberia Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Monteverde' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Nosara' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Occidental Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Ostional' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Papagayo Peninsula' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Penas Blancas (Nicaragua Border)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Planet Hollywood Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Azul, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa del Coco, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Guiones' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Hermosa, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Ocotal, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Playa Panama, Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Islita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Punta Leona' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Quepos' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rincon de la Vieja' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Celeste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rio Perdido' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Guanacaste' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'RIU Palace' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Samara / Playa Carrillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose (Downtown)' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Jose Airport' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'San Juanillo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Secrets Papagayo' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tambor' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Tenorio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'The Springs Resort & Spa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Lost Iguana Resort' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Rancho Margot' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Hotel Linda Vista  ' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Arenal Vista Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Leaves & Lizards' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Chachagua Rainforest Hotel' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Heliconias Nature Lodge' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Dominical' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                    'Uvita' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Imperial Beer (6 pack)',
                ],
                'San Jose' => [
                    'Liberia' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
                'Manuel Antonio' => [
                    'Montezuma' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    'Mal-Pais / Santa Teresa' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
                'Montezuma' => [
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
                'Mal-Pais / Santa Teresa' => [
                    'Manuel Antonio' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                    'Jaco' => 'Extra Time: 1 hour,Infant Car Seat,Toddler Car Seat,Booster Car Seat,Champagne,Imperial Beer (6 pack)',
                ],
            
            ];            

            foreach ($xlsx->rows() as $key => $r) {
                if ($key == 0) continue;

                $data_transportation_type = $r[0];
                $data_area_from = $r[1];
                $data_area_to = $r[2];

                if (empty($data_area_to)) continue;

                $data_vehicule_type                             = $r[3];
                $data_departure_time                            = $r[4];
                $data_km                                        = $r[5];
                $data_duration                                  = $r[6];
                $data_note                                      = $r[7];
                $data_enabled                                   = $r[8];
                $data_vehicle_rack_price                        = $r[9];
                $data_vehicle_net_price                         = $r[10];
                $data_adult_rack_price                          = $r[11];
                $data_adult_net_price                           = $r[12];
                $data_kid_rack_price                            = $r[13];
                $data_kid_net_price                             = $r[14];
                $data_tax                                       = "0.08";
                $data_addons                                    = $r[17];
                $data_extras                                    = $r[16];

                $data_regular_time_range_enabled                = $r[18];
                $data_range_start_regular_time_range            = $r[19];
                $data_range_end_regular_time_range              = $r[20];
                $data_rack_price_regular_time_range             = $r[21];
                $data_net_price_regular_time_range              = $r[22];
                $data_tax_regular_time_range                    = $r[23];

                $data_flight_pickup_time_range_enabled          = $r[24];
                $data_range_start_flight_pickup_time_range      = $r[25];
                $data_range_end_flight_pickup_time_range        = $r[26];
                $data_rack_price_flight_pickup_time_range       = $r[27];
                $data_net_price_flight_pickup_time_range        = $r[28];
                $data_tax_flight_pickup_time_range              = $r[29];

                $data_flight_drop_off_time_range_enabled        = $r[30];
                $data_range_start_flight_drop_off_time_range    = $r[31];
                $data_range_end_flight_drop_off_time_range      = $r[32];
                $data_rack_price_flight_drop_off_time_range     = $r[33];
                $data_net_price_flight_drop_off_time_range      = $r[34];
                $data_tax_flight_drop_off_time_range            = $r[35];
                if( $data_area_from == "Manuel Antonio | Quepos" ){ $data_area_from = "Quepos/Manuel Antonio"; }
                if( $data_area_to == "Manuel Antonio | Quepos" ){ $data_area_to = "Quepos/Manuel Antonio"; }
                
                if( $data_area_from == "Quepos/Manuel Antonio | Quepos" ){ $data_area_from = "Quepos/Manuel Antonio"; }
                if( $data_area_to == "Quepos/Manuel Antonio | Quepos" ){ $data_area_to = "Quepos/Manuel Antonio"; }
                
                if( $data_area_from == "Curridibat" ){ $data_area_from = "Curridabat"; }
                if( $data_area_to == "Curridibat" ){ $data_area_to = "Curridabat"; }
                
                if( $data_area_from == "Costa Rica Marriottt (belen)" ){ $data_area_from = "Costa Rica Marriott San Jose (Belen)"; }
                if( $data_area_to == "Costa Rica Marriottt (belen)" ){ $data_area_to = "Costa Rica Marriott San Jose (Belen)"; }

                if( $data_area_from == "Alajeula" ){ $data_area_from = "Alajuela"; }
                if( $data_area_to == "Alajeula" ){ $data_area_to = "Alajuela"; }
                
                if( $data_area_from == "The Westin Playa Conhcal" ){ $data_area_from = "The Westin Playa Conchal"; }
                if( $data_area_to == "The Westin Playa Conhcal" ){ $data_area_to = "The Westin Playa Conchal"; }
               
                if( $data_area_from == "Samara / Playa Carrillo" ){ $data_area_from = "Samara/Playa Carrillo"; }
                if( $data_area_to == "Samara / Playa Carrillo" ){ $data_area_to = "Samara/Playa Carrillo"; }
               
                if( $data_area_from == "Mal-Pais / Santa Teresa" ){ $data_area_from = "Mal-Pais/Santa Teresa"; }
                if( $data_area_to == "Mal-Pais / Santa Teresa" ){ $data_area_to = "Mal-Pais/Santa Teresa"; }

                if( $data_duration == "45m" ){
                    $data_duration = 0.75;
                }else if( $data_duration == "30m" ){
                    $data_duration = 0.50;
                }

                $area_from                                      = $this->em->getRepository(Area::class)->findOneByName($data_area_from);
                if( is_null( $area_from ) ){
                    $io->writeln( "Area not exists: ".       $data_area_from );
                }
                $area_to                                        = $this->em->getRepository(Area::class)->findOneByName($data_area_to);
                if( is_null( $area_to ) ){
                    $io->writeln( "Area not exists: ".       $data_area_to );
                }


                $vehicule_type                                  = $this->em->getRepository(VehicleType::class)->findOneByName($data_vehicule_type);
                $transportationType                             = $this->em->getRepository(TransportationType::class)->findOneByName($data_transportation_type);

                $alreadyAddedShuttles                           = $this->em->getRepository(Product::class)->findBy([
                    'transportationType'    => [$transportationType],
                    'areaFrom'              => $area_from,
                    'areaTo'                => $area_to,
                    'vehicleType'           => $vehicule_type,
                    'enabled'               => 1,
                    'archived'              => 0,

                ]);
               
                if( count( $alreadyAddedShuttles ) > 0 ){
                    foreach ( $alreadyAddedShuttles as  $shuttle_key => $shuttle_obj ) {
                        if( $shuttle_key == 0 ){ 
                            $product = $shuttle_obj;
                        }else{
                            $shuttle_obj->setArchived(true);
                            $this->em->persist($shuttle_obj);
                            $io->writeln( "Deleting shuttle due to duplication 1: " );
                            $io->writeln( "shuttle ID: ".       $shuttle_obj->getId() );
                            $io->writeln( "area_from: ".        $area_from );
                            $io->writeln( "area_to: ".          $area_to );
                            $io->writeln( "vehicule_type: ".    $vehicule_type );                        
                            $io->writeln( "------------------------------" );
                            
                        }
                    }
                }else{
                    $product = new Product;
                }

                
                // Transportation type (prendre l'une des valeurs suivantes : Private shuttles, Shared shuttles, Jeep-Boat-Jeep shared, Jeep-Boat-Jeep private, Jeep-Boat-Jeep riding, Water Taxi, Private Flight) 
                $product->setTransportationType($transportationType);
                // Area from (un nom dans BO > Area. à copier exactement un nom existant ou créer sinon. exemple : Montezuma)
                $product->setAreaFrom($area_from);
                // Area to (un nom dans BO > Area. à copier exactement un nom existant ou créer sinon. exemple : Montezuma)

                $product->setAreaTo($area_to);

                // Vehicle Type (un nom dans BO > Vehicule Types. à copier exactement un nom existant ou créer sinon. exemple : Cessna Grand Caravan)
                $product->setVehicleType($vehicule_type);
                // Departure time (exemple de format 23:59)  
                if ($data_departure_time) {
                    $departure_time = new \Datetime($data_departure_time);
                    $product->setDepartureTime($departure_time);
                }
                // km (exemple : 200)
                $product->setKm($data_km);
                // Duration (exemple : 3)
                $data_duration = ($data_duration == '-') ? '0' : $data_duration;
                $data_duration = ($data_duration == '') ? '0' : $data_duration;
                $product->setDuration($data_duration);
                // Note (texte en html)
                $product->setNote($data_note);
                // Enabled (1 ou 0)
                $product->setEnabled((bool)$data_enabled);
                // Vehicle rack price
                $data_vehicle_rack_price = ($data_vehicle_rack_price == '') ? '0' : $data_vehicle_rack_price;
                $data_vehicle_rack_price = ($data_vehicle_rack_price == '-') ? '0' : $data_vehicle_rack_price;
                $product->setFixedRackPrice($data_vehicle_rack_price);
                // Vehicle net price
                $data_vehicle_net_price = ($data_vehicle_net_price == '-') ? '0' : $data_vehicle_net_price;
                $data_vehicle_net_price = ($data_vehicle_net_price == '') ? '0' : $data_vehicle_net_price;
                $product->setFixedNetPrice($data_vehicle_net_price);
                // Adult rack price
                // $product->setAdultRackPrice($data_adult_rack_price);
                // // Adult net price
                // $product->setAdultNetPrice($data_adult_net_price);
                // // Kid rack price
                // $product->setChildRackPrice($data_kid_rack_price);
                // // Kid net price
                // $product->setChildNetPrice($data_kid_net_price);

                // Tax (0 ou 0.04 ou 0.13)
                $tax = $this->getTaxByString($data_tax);

                $product->setTax($tax);
                // Addons (a label in BO > Addon. to copy an existing label exactly or otherwise create. example: Poas Volcano. Separate the name with a comma if multiple choice) 
                $data_addons_array_string = explode(',', $data_addons);
                foreach ($data_addons_array_string as $data_addon_array_string) {
                    if ($data_addon_array_string) {
                        $addon = $this->em->getRepository(Addon::class)->findOneByLabel($data_addon_array_string);
                        $product->addAddon($addon);
                    }
                }
                // Extras (a label in BO > Extra. to copy an existing label exactly or otherwise create. example: Poas Champagne. Separate the name with a comma if multiple choice) 
                if( empty( $data_extras ) ){

                    if( array_key_exists( $data_area_from, $allProdExtras ) ){
                        if( array_key_exists( $data_area_to, $allProdExtras[$data_area_from] ) ){
                            
                            $data_extras = $allProdExtras[$data_area_from][$data_area_to];
                          
                        }   
                    }else if( array_key_exists( $data_area_to, $allProdExtras ) ){
                        if( array_key_exists( $data_area_from, $allProdExtras[$data_area_to] ) ){
                            
                            $data_extras = $allProdExtras[$data_area_to][$data_area_from];
                          
                        }   
                    }
                } 

                $data_extras_array_string = explode(',', $data_extras);
                foreach ($data_extras_array_string as $data_extra_array_string) {
                    if ($data_extra_array_string) {
                        $extra = $this->em->getRepository(Extra::class)->findOneByLabel($data_extra_array_string);
                        $product->addExtra($extra);
                    }
                }                
                
                $this->em->persist($product);

                $alreadyAddedShuttles = $this->em->getRepository(Product::class)->findBy([
                    'transportationType'    => [$transportationType],
                    'areaFrom'              => $area_from,
                    'areaTo'                => $area_to,
                    'vehicleType'           => $vehicule_type,
                    'enabled'               => 1,
                    'archived'              => 0,

                ]);
               
                if( count( $alreadyAddedShuttles ) > 0 ){
                    foreach ( $alreadyAddedShuttles as  $shuttle_key => $shuttle_obj ) {
                        if( $shuttle_key != 0 ){ 
                            
                            $shuttle_obj->setArchived(true);
                            $this->em->persist($shuttle_obj);
                            $io->writeln( "Deleting shuttle due to duplication 2: " );
                            $io->writeln( "shuttle ID: ".       $shuttle_obj->getId() );
                            $io->writeln( "area_from: ".        $area_from );
                            $io->writeln( "area_to: ".          $area_to );
                            $io->writeln( "vehicule_type: ".    $vehicule_type );                        
                            $io->writeln( "------------------------------" );
                            
                        }
                    }
                }
                $io->writeln('product imported: number #' . $key);
            }

            $this->em->flush();

            foreach ($xlsx->rows() as $key => $r) {
                if ($key == 0) continue;

                $data_transportation_type = $r[0];
                $data_area_from = $r[1];
                $data_area_to = $r[2];
                if (empty($data_area_to)) continue;
                $data_vehicule_type                             = $r[3];

                $area_from                                      = $this->em->getRepository(Area::class)->findOneByName($data_area_from);
                $area_to                                        = $this->em->getRepository(Area::class)->findOneByName($data_area_to);
                $vehicule_type                                  = $this->em->getRepository(VehicleType::class)->findOneByName($data_vehicule_type);
                $transportationType                             = $this->em->getRepository(TransportationType::class)->findOneByName($data_transportation_type);

             
                $alreadyAddedShuttles = $this->em->getRepository(Product::class)->findBy([
                    'transportationType'    => [$transportationType],
                    'areaFrom'              => $area_from,
                    'areaTo'                => $area_to,
                    'vehicleType'           => $vehicule_type,
                    'enabled'               => 1,
                    'archived'              => 0,

                ]);
               
                if( count( $alreadyAddedShuttles ) > 0 ){
                    foreach ( $alreadyAddedShuttles as  $shuttle_key => $shuttle_obj ) {
                        if( $shuttle_key != 0 ){ 
                            
                            $shuttle_obj->setArchived(true);
                            $this->em->persist($shuttle_obj);
                            $io->writeln( "Deleting shuttle due to duplication 3: " );
                            $io->writeln( "shuttle ID: ".       $shuttle_obj->getId() );
                            $io->writeln( "area_from: ".        $area_from );
                            $io->writeln( "area_to: ".          $area_to );
                            $io->writeln( "vehicule_type: ".    $vehicule_type );                        
                            $io->writeln( "------------------------------" );
                        }
                    }
                }                
            }

            $this->em->flush();
            $io->success('Finished!');
        } else {
            echo 'xlsx error: ' . $xlsx->error();
        }

    }

    public function getTaxByString($data_tax)
    {
        $tax = null;
        if ($data_tax == '0') {
            $tax = $this->tax1;
        } elseif ($data_tax == '0.04') {
            $tax = $this->tax2;
        } elseif ($data_tax == '0.13') {
            $tax = $this->tax3;
        } elseif ($data_tax == '0.08') {
            $tax = $this->tax4;
        }
        return $tax;
    }
}
