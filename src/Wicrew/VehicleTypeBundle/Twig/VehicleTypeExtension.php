<?php

namespace App\Wicrew\VehicleTypeBundle\Twig;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\Order;
use App\Wicrew\SaleBundle\Entity\OrderHistory;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasDriver;
use App\Wicrew\SaleBundle\Service\OrderService;
use App\Wicrew\VehicleBundle\Entity\Vehicle;
use App\Wicrew\VehicleTypeBundle\Entity\VehicleType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VehicleTypeExtension extends AbstractExtension {

    /**
     * Core utility class
     *
     * @var Utils
     */
    private $utils;

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
     * @return VehicleTypeExtension
     */
    public function setUtils(Utils $utils): VehicleTypeExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('getVehicleNoteList', [$this, 'getVehicleNoteList']),
            new TwigFunction('getVehicleNoteListByName', [$this, 'getVehicleNoteListByName']),
            new TwigFunction('getVehicleMaxPassengerByName', [$this, 'getVehicleMaxPassengerByName'])

        ];
    }

    /**
     * get Order data
     *
     * @param VehicleType $vehicle
     *
     * @return string[]
     */
    public function getVehicleNoteList(VehicleType $vehicle): array {
        $notes = $vehicle->getNotes();
        $notesArr = preg_split("/\r\n|\n|\r/", $notes);
        if ($vehicle->isAirConditioning()) {
            $notesArr[] = "Air conditioning";
        }

        return $notesArr;
    }

    /**
     * get Order data
     *
     * @param string $vehicleName
     *
     * @return string[]
     */
    public function getVehicleNoteListByName(string $vehicleName): array {
        $vehicle = $this->getUtils()->getEntityManager()->getRepository(VehicleType::class)->findOneBy([ 'name' => $vehicleName]);
        if ($vehicle !== null) {
            return $this->getVehicleNoteList($vehicle);
        }

        return array();
    }


    /**
     * get Vehicle data
     *
     * @param string $vehicleName
     *
     * @return string[]
     */
    public function getVehicleMaxPassengerByName(string $vehicleName) {
        // $vehicle = $this->getUtils()->getEntityManager()->getRepository(VehicleType::class)->findOneBy([ 'name' => $vehicleName]);
        
        // The value you want to search for
        $searchValue = '%'.$vehicleName.'%'; // Replace with the actual search term

        // Retrieve vehicles using LIKE comparison
        $vehicles = $this->getUtils()->getEntityManager()->getRepository(VehicleType::class)
            ->createQueryBuilder('e')
            ->where('e.name LIKE :searchValue')
            ->setParameter('searchValue', $searchValue)
            ->getQuery()
            ->getResult();

       
        if ($vehicles !== null) {
            $max_passengers_arr = array();        
            foreach( $vehicles as $vehicle ){
                $max_passengers = $vehicle->getMaxPassengerNumber();
                array_push($max_passengers_arr, $max_passengers);
            }

            return max($max_passengers_arr);
        }else{
            return 0;
        }

    }
}
