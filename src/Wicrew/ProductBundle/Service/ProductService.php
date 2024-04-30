<?php

namespace App\Wicrew\ProductBundle\Service;

use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\ProductBundle\Entity\Area;
use App\Wicrew\ProductBundle\Entity\AreaChildren;
use App\Entity\User;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\SaleBundle\Service\Summary\PriceSummary;
use App\Wicrew\SaleBundle\Service\Summary\ProductSummary;
use DateTime;

/**
 * Product
 */
class ProductService {
    /**
     * utils
     *
     * @var Utils
     */
    protected $utils;

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
     * @return ProductService
     */
    public function setUtils(Utils $utils): ProductService {
        $this->utils = $utils;
        return $this;
    }

    /**
     * Get Area text
     *
     * @param int $id
     *
     * @return string
     */
    public function getAreaText($id) {
        $em = $this->getUtils()->getEntityManager();
        if (strpos($id, '-') !== false) {
            $id = substr($id, strpos($id, '-') + 1);
            $area = $em->getRepository(AreaChildren::class)->find($id);
        }else{
            $area = $em->getRepository(Area::class)->find($id);
        }

        return $area->getName();
    }

    /**
     * Get Area text
     *
     * @param int $id
     *
     * @return string
     */
    public function getAreaType(int $id) {
        $em = $this->getUtils()->getEntityManager();
        $area = $em->getRepository(Area::class)->find($id);

        return $area->getType();
    }

    /**
     * Search a products from filter form
     *
     * @param int[] $transportTypeID
     * @param int $areaFromID
     * @param int $areaToID
     * @param int $adultCount
     * @param int $childCount
     *
     * @return Product[]
     */
    public function searchProduct(array $transportTypeID, int $areaFromID, int $areaToID, int $adultCount, int $childCount): array {
        $passengerCount = $adultCount + $childCount;

        $em = $this->getUtils()->getEntityManager();
        $productsQB = $em->getRepository(Product::class)->createQueryBuilder('prd')->select('prd')
            ->where('prd.enabled = :status')
            ->setParameter('status', Product::PRODUCT_STATUS_ENABLED)
            ->innerJoin('prd.transportationType', 'tt')
            ->andWhere('tt.id in (:transportIDs)')
            ->setParameter('transportIDs', $transportTypeID)
            ->innerJoin('prd.areaFrom', 'af')
            ->innerJoin('prd.areaTo', 'at')
            ->andWhere('(af.id = :afID AND at.id = :atID) OR (af.id = :atID AND at.id = :afID)')
            ->andWhere('prd.archived = :archived')
            ->setParameter('archived', false)
            ->setParameter('afID', $areaFromID)
            ->setParameter('atID', $areaToID);
        if( !$this->getUtils()->getContainer()->get('security.authorization_checker')->isGranted('ROLE_EMPLOYEE') ){
            if (in_array(TransportationType::TYPE_PRIVATE_SHUTTLE, $transportTypeID)) { 
                $productsQB->andWhere('vt.maxPassengerNumber >= :passengerCount');
                $productsQB->andWhere('vt.minPassengerNumber <= :passengerCount');
                $productsQB->setParameter('passengerCount', $passengerCount);
            }
        }

        $productsQB->innerJoin('prd.vehicleType', 'vt');
        $productsQB->orderBy('vt.maxPassengerNumber', 'ASC');

        return $productsQB->getQuery()->getResult();
    }

    /**
     * Search a products by transportation type
     *
     * @param int $transportationType
     *
     * @return Product[]
     */
    public function productsByTransportationType(int $transportationType) {
        $em = $this->getUtils()->getEntityManager();
        return $em->getRepository(Product::class)->createQueryBuilder('prd')
            ->innerJoin('prd.transportationType', 'tt')
            ->where('tt.id = (:tst) AND prd.enabled = (:sta)')
            ->setParameter('tst', $transportationType)
            ->setParameter('sta', Product::PRODUCT_STATUS_ENABLED)
            ->orderBy('prd.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * Calculate product price
     *
     * @param Product $product
     * @param int $adultCount
     * @param int $childCount
     * @param array $areaToInputInfo
     * @param array $areaFromInputInfo
     * @param DateTime|null $pickupDate
     * @param DateTime|null $pickUpTime
     * @param array|null $addons
     *
     * @return ProductSummary
     */
    public function getPriceSummary(Product $product, int $adultCount, int $childCount, array $areaFromInputInfo, array $areaToInputInfo, ?DateTime $pickUpTime = null, ?DateTime $pickupDate = null, ?array $addons = null, ?array $extras = null, ?array $custom_services = []): ProductSummary {
        $em = $this->getUtils()->getEntityManager();
        $translator = $this->getUtils()->getTranslator();
        $summary = new ProductSummary($product, $em, $translator, $this->getUtils()->getContainer()->get('kernel'), $adultCount, $childCount, $areaFromInputInfo, $areaToInputInfo, $pickUpTime, $pickupDate, $addons, $extras, $custom_services);
        $uploadHelper = $this->getUtils()->getContainer()->get('vich_uploader.templating.helper.uploader_helper');
        $summary->setImage($uploadHelper->asset($product->getVehicleType(), 'imageFile'));

        return $summary;
    }
}
