<?php


namespace App\Wicrew\SaleBundle\Service\Summary;


use App\Wicrew\CoreBundle\Service\Money;
use App\Wicrew\ProductBundle\Entity\Area;

class AreaSummary {
    private $identifier;

    public function getIdentifier(): int {
        return $this->identifier;
    }

    /**
     * @var int
     */
    private $type;

    public function getType(): int {
        return $this->type;
    }

    /**
     * @var string
     */
    private $name;

    public function getName(): string {
        return $this->name;
    }

    /**
     * @var string Only used for hotel-type areas.
     */
    private $address;

    public function getAddress(): ?string {
        return $this->address;
    }

    /**
     * @var string Only used for hotel-type areas.
     */
    private $googlePlaceID;

    public function getGooglePlaceID(): string {
        return $this->googlePlaceID;
    }

    /**
     * @var string Only used for airport-type areas.
     */
    private $flightNumber;

    public function getFlightNumber(): ?string {
        return $this->flightNumber;
    }

    /**
     * @var string Only used for airport-type areas.
     */
    private $airlineCompany;

    public function getAirlineCompany(): ?string {
        return $this->airlineCompany;
    }

    /**
     * @var SummaryElement|null
     */
    private $additionalFee = null;

    public function getAdditionalFee(): SummaryElement {
        return $this->additionalFee;
    }

    public function hasAddFee(): bool {
        return $this->additionalFee !== null;
    }

    /**
     * @param Money $rackPrice
     * @param Money $netPrice
     * @param string $hotelName
     */
    public function setAdditionalFee(Money $rackPrice, Money $netPrice, string $hotelName): void {
        $this->additionalFee = new SummaryElement($rackPrice, $netPrice, $hotelName);
    }

    /**
     * AreaSummary constructor.
     *
     * @param int $id
     * @param string $name
     * @param string $address
     *
     * @param string $googlePlaceID
     *
     * @return AreaSummary
     */
    public static function constructArea(int $id, string $name, string $address, string $googlePlaceID): AreaSummary {
        $thois = new AreaSummary();
        $thois->identifier = $id;
        $thois->type = Area::TYPE_AREA;
        $thois->name = $name;

        $thois->address = $address;
        $thois->googlePlaceID = $googlePlaceID;

        return $thois;
    }

    /**
     * AreaSummary constructor.
     *
     * @param int $id
     * @param string $name
     * @param string $flightNumber
     * @param string $airlineCompany
     *
     * @return AreaSummary
     */
    public static function constructAirport(int $id, string $name, string $flightNumber, string $airlineCompany): AreaSummary {
        $thois = new AreaSummary();
        $thois->identifier = $id;
        $thois->type = Area::TYPE_AIRPORT;
        $thois->name = $name;

        $thois->flightNumber = $flightNumber;
        $thois->airlineCompany = $airlineCompany;

        return $thois;
    }
}