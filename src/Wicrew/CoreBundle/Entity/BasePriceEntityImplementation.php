<?php /** @noinspection PhpIncompatibleReturnTypeInspection */


namespace App\Wicrew\CoreBundle\Entity;


use App\Wicrew\SaleBundle\Entity\Tax;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait BasePriceEntityImplementation {
    /**
     * Price type
     *
     * @var int|null
     *
     * @Assert\Length(max = 1)
     *
     * @ORM\Column(name="price_type", type="integer", length=1, nullable=false, options={"unsigned"=true, "default"="1", "comment"="1 = Per person, 2 = For the trip"})
     */
    private $priceType = IBasePriceEntity::PRICE_TYPE_PER_PERSON;

    /**
     * @return int|null
     */
    public function getPriceType(): ?int {
        return $this->priceType;
    }

    /**
     * @param int|null $priceType
     *
     * @return IBasePriceEntity
     */
    public function setPriceType(?int $priceType): IBasePriceEntity {
        $this->priceType = $priceType;
        return $this;
    }

    /**
     * Fixed rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="fixed_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $fixedRackPrice = '0.00';

    /**
     * @return string
     */
    public function getFixedRackPrice(): string {
        return $this->fixedRackPrice;
    }

    /**
     * @param string $fixedRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setFixedRackPrice(string $fixedRackPrice): IBasePriceEntity {
        $this->fixedRackPrice = $fixedRackPrice;
        return $this;
    }

    /**
     * Fixed net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="fixed_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $fixedNetPrice = '0.00';

    /**
     * @return string
     */
    public function getFixedNetPrice(): string {
        return $this->fixedNetPrice;
    }

    /**
     * @param string $fixedNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setFixedNetPrice(string $fixedNetPrice): IBasePriceEntity {
        $this->fixedNetPrice = $fixedNetPrice;
        return $this;
    }

    /**
     * Adult rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $adultRackPrice = '0.00';

    /**
     * @return string
     */
    public function getAdultNetPrice(): string {
        return $this->adultNetPrice;
    }

    /**
     * @param string $adultNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setAdultNetPrice(string $adultNetPrice): IBasePriceEntity {
        $this->adultNetPrice = $adultNetPrice;
        return $this;
    }

    /**
     * Adult net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="adult_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $adultNetPrice = '0.00';

    /**
     * @return string
     */
    public function getAdultRackPrice(): string {
        return $this->adultRackPrice;
    }

    /**
     * @param string $adultRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setAdultRackPrice(string $adultRackPrice): IBasePriceEntity {
        $this->adultRackPrice = $adultRackPrice;
        return $this;
    }

    /**
     * Adult rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_rack_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $childRackPrice = '0.00';

    /**
     * @return string
     */
    public function getChildRackPrice(): string {
        return $this->childRackPrice;
    }

    /**
     * @param string $childRackPrice
     *
     * @return IBasePriceEntity
     */
    public function setChildRackPrice(string $childRackPrice): IBasePriceEntity {
        $this->childRackPrice = $childRackPrice;
        return $this;
    }

    /**
     * Adult net price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="child_net_price", type="decimal", precision=10, scale=2, nullable=false, options={"default"="0.00"})
     */
    private $childNetPrice = '0.00';

    /**
     * @return string
     */
    public function getChildNetPrice(): string {
        return $this->childNetPrice;
    }

    /**
     * @param string $childNetPrice
     *
     * @return IBasePriceEntity
     */
    public function setChildNetPrice(string $childNetPrice): IBasePriceEntity {
        $this->childNetPrice = $childNetPrice;
        return $this;
    }

    /**
     * Pricing tax
     *
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id")
     */
    private $tax;

    /**
     * @return Tax|null
     */
    public function getTax(): ?Tax {
        return $this->tax;
    }

    /**
     * @param Tax|null $tax
     *
     * @return IBasePriceEntity
     */
    public function setTax(?Tax $tax): IBasePriceEntity {
        $this->tax = $tax;
        return $this;
    }
}