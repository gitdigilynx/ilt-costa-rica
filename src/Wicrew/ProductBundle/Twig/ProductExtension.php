<?php

namespace App\Wicrew\ProductBundle\Twig;

use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\AddonOption;
use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\ProductBundle\Service\ProductService;
use App\Wicrew\SaleBundle\Service\Summary\PriceSummary;
use Doctrine\ORM\QueryBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * CircuitExtension
 */
class ProductExtension extends AbstractExtension {

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
     * @return ProductExtension
     */
    public function setUtils(Utils $utils): ProductExtension {
        $this->utils = $utils;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('loadProduct', [$this, 'loadProduct']),
            new TwigFunction('jeepBoatJeepTypes', [$this, 'jeepBoatJeepTypes']),
            new TwigFunction('getAreaTextById', [$this, 'getAreaTextById']),
            new TwigFunction('getAreasByProductAvailability', [$this, 'getAreasByProductAvailability']),
            new TwigFunction('getAreaTypeById', [$this, 'getAreaTypeById']),
            new TwigFunction('getWaterTaxiTransportType', [$this, 'getWaterTaxiTransportType']),
            new TwigFunction('getDepartureTimeTexts', [$this, 'getDepartureTimeTexts']),
            new TwigFunction('getDepartureOptions', [$this, 'getDepartureOptions']),
            new TwigFunction('getOrderedProductsbyDepartureTime', [$this, 'getOrderedProductsbyDepartureTime']),
        ];
    }

    /**
     * Get Product by id
     *
     * @param int $id
     * @param int $enable
     * @param null $switchArea
     *
     * @return Product
     */
    public function loadProduct(int $id, $enable = Product::PRODUCT_STATUS_ENABLED, $switchArea = null): Product {
        $em = $this->getUtils()->getEntityManager();
        $productsQB = $em->getRepository('\App\Wicrew\ProductBundle\Entity\Product')->createQueryBuilder('prd')->select('prd');
        $productsQB->where('prd.id = (:id) AND prd.enabled = (:sta)');
        $productsQB->setParameters(['id' => $id, 'sta' => $enable]);
        $productFound = $productsQB->getQuery()->getSingleResult();
        if ($switchArea) {
            $areaFrom = $productFound->getAreaFrom();
            $areaTo = $productFound->getAreaTo();
            $productFound->setAreaFrom($areaTo);
            $productFound->setAreaTo($areaFrom);
        }

        return $productFound;
    }

    public function getWaterTaxiTransportType(): TransportationType {
        $em = $this->getUtils()->getEntityManager();

        return $em->getRepository(TransportationType::class)->find(TransportationType::TYPE_WATER_TAXI);
    }

    /**
     * Get Jeep boat jeep types
     *
     * @return TransportationType[]
     */
    public function jeepBoatJeepTypes() {
        $em = $this->getUtils()->getEntityManager();

        return $em->getRepository(TransportationType::class)
            ->findBy(['urlPath' => TransportationType::TYPE_JEEP_BOAT_JEEP_URL]);
    }

    /**
     * Get area type by id
     *
     * @param int[] $transportTypeIDs
     *
     * @return Area[]
     */
    public function getAreasByProductAvailability(array $transportTypeIDs): array {
        $areaFromQB = $this->getUtils()->getEntityManager()->getRepository(Area::class)->createQueryBuilder('area')
            ->innerJoin('area.fromProducts', 'product');
        $this->applyAreaFilter($areaFromQB, $transportTypeIDs);

        $areaToQB = $this->getUtils()->getEntityManager()->getRepository(Area::class)->createQueryBuilder('area')
            ->innerJoin('area.toProducts', 'product');
        $this->applyAreaFilter($areaToQB, $transportTypeIDs);

        $areaFrom = $areaFromQB->getQuery()->getResult();
        $areaTo = $areaToQB->getQuery()->getResult();

        $result = array_merge($areaFrom, $areaTo);
        $result = array_unique($result);

        usort($result, function($a, $b) { 
            if ($a->getName() == $b->getName()) {
                return 0;
            }
            return ($a->getName() < $b->getName()) ? -1 : 1;
        });

        return $result;
    }

    private function applyAreaFilter(QueryBuilder &$qb, array $transportTypeIDs): void {
        $qb = $qb->where('product.enabled = :sta')
            ->setParameter('sta', Product::PRODUCT_STATUS_ENABLED)
            ->innerJoin('product.transportationType', 'transportationType')
            ->andWhere('transportationType.id in (:transportIDs)')
            ->setParameter('transportIDs', $transportTypeIDs);
    }

    /**
     * Get area text by id
     *
     * @param int $id
     *
     * @return string
     */
    public function getAreaTextById($id) {
        $productUtil = new ProductService($this->getUtils());
        $area = $productUtil->getAreaText($id);

        return $area ? $area : $id;
    }

    /**
     * Get area type by id
     *
     * @param int $id
     *
     * @return int
     */
    public function getAreaTypeById(int $id) {
        $productUtil = new ProductService($this->getUtils());
        $area = $productUtil->getAreaType($id);

        return $area ? $area : $id;
    }

    public function getDepartureTimeTexts($products) {
        $texts = [];
        $texts_by_area_from = [];

        foreach ($products as $key => $product) {
            $texts_by_area_from[base64_encode($product->getAreaFrom())][] = $product->getDepartureTime();
        }
        
        $trans = $this->getUtils()->getTranslator();

        foreach ($texts_by_area_from as $area_from => $departure_times) {
            $departure_times_array = [];
            foreach ($departure_times as $key => $departure_time) {
                if ($departure_time) $departure_times_array[] = $departure_time->format("H:i");
            }
           
            usort($departure_times_array, function ($a, $b)
            {
                $a = strtotime($a);
                $b = strtotime($b);
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });

            if ($departure_times_array) {
                $texts[] = implode(' / ', $departure_times_array) .  ' ' . $trans->trans('from') . ' ' .  base64_decode($area_from);
            }
        }
        
        return $texts;
    }

    public function getOrderedProductsbyDepartureTime($products) {
        $products_ordered = []; 
        $products_data = []; 

        foreach ($products as $key => $product) {
            $products_data[$product->getId()] = [
                'departure_time' => $product->getDepartureTime(),
                'product' => $product
            ];
        }
        
        $trans = $this->getUtils()->getTranslator();

        usort($products_data, function ($a, $b)
            {
                $departure_time_a = $a['departure_time'];
                $departure_time_b = $b['departure_time'];
                 
                if ($departure_time_a == $departure_time_b) {
                    return 0;
                }
                return ($departure_time_a < $departure_time_b) ? -1 : 1;
        });

        foreach ($products_data as $key => $product_data) {
            $products_ordered[] = $product_data['product'];
        }
    
        return $products_ordered;
    }

    public function getDepartureOptions($products) { 
        $texts_by_area = [];

        foreach ($products as $key => $product) {
            $texts_by_area[$product->getAreaFrom()->getId() . "_" . $product->getAreaTo()->getId()]['product'] = $product;
            $texts_by_area[$product->getAreaFrom()->getId() . "_" . $product->getAreaTo()->getId()]['productIDs'][] = $product->getId();
        }
         
        return $texts_by_area;
    }
}
