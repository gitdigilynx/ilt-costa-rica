<?php


namespace App\Wicrew\CoreBundle\Entity;


use App\Wicrew\SaleBundle\Entity\Tax;


interface IBasePriceEntity {
    /**
     * Price types
     */
    public const PRICE_TYPE_PER_PERSON = 1;
    public const PRICE_TYPE_FOR_THE_TRIP = 2;
    
    /**
     * @return int|null
     */
    public function getPriceType(): ?int;

    /**
     * @param int|null $priceType
     *
     * @return BasePriceEntity
     */
    public function setPriceType(?int $priceType);

    /**
     * @return string
     */
    public function getFixedRackPrice(): string;

    /**
     * @param string $fixedRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setFixedRackPrice(string $fixedRackPrice): IBasePriceEntity;

    /**
     * @return string
     */
    public function getFixedNetPrice(): string;

    /**
     * @param string $fixedNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setFixedNetPrice(string $fixedNetPrice): IBasePriceEntity;

    /**
     * @return string
     */
    public function getAdultNetPrice(): string;

    /**
     * @param string $adultNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setAdultNetPrice(string $adultNetPrice): IBasePriceEntity;

    /**
     * @return string
     */
    public function getAdultRackPrice(): string;

    /**
     * @param string $adultRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setAdultRackPrice(string $adultRackPrice): IBasePriceEntity;

    /**
     * @return string
     */
    public function getChildRackPrice(): string;

    /**
     * @param string $childRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setChildRackPrice(string $childRackPrice): IBasePriceEntity;

    /**
     * @return string
     */
    public function getChildNetPrice(): string;

    /**
     * @param string $childNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setChildNetPrice(string $childNetPrice): IBasePriceEntity;

    /**
     * @return Tax|null
     */
    public function getTax(): ?Tax;

    /**
     * @param Tax|null $tax
     *
     * @return IBasePriceEntity
     */
    public function setTax(?Tax $tax): IBasePriceEntity;
}