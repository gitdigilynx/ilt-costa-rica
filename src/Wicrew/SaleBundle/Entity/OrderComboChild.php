<?php


namespace App\Wicrew\SaleBundle\Entity;


use App\Wicrew\ActivityBundle\Entity\ActivityHasChild;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Service\Money;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="OrderComboChild",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})},
 *     indexes={
 *     @ORM\Index(name="fk_OrderComboChild_ActivityHasChild_idx", columns={"activity_child_id"}),
 *     @ORM\Index(name="fk_OrderComboChild_OrderItem_idx", columns={"order_item_id"})
 * }
 * )
 */
class OrderComboChild extends BaseEntity {
    /**
     * ID
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return OrderComboChild
     */
    public function setId(int $id): OrderComboChild {
        $this->id = $id;
        return $this;
    }

    /**
     * OrderItem
     *
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="comboChildren", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $orderItem;

    /**
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem {
        return $this->orderItem;
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return OrderComboChild
     */
    public function setOrderItem(OrderItem $orderItem): OrderComboChild {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Activity
     *
     * @var ActivityHasChild
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\ActivityBundle\Entity\ActivityHasChild", cascade={"persist"})
     * @ORM\JoinColumn(name="activity_child_id", referencedColumnName="id")
     */
    private $activityChild;

    /**
     * @return ActivityHasChild
     */
    public function getActivityChild(): ActivityHasChild {
        return $this->activityChild;
    }

    /**
     * @param ActivityHasChild $activityChild
     *
     * @return OrderComboChild
     */
    public function setActivityChild(ActivityHasChild $activityChild): OrderComboChild {
        $this->activityChild = $activityChild;
        return $this;
    }

    /**
     * Adult rack
     *
     * @var string
     *
     * @ORM\Column(name="adult_rack", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $adultRack;

    /**
     * @return string
     */
    public function getAdultRack(): string {
        return $this->adultRack;
    }

    /**
     * @param string $adultRack
     *
     * @return OrderComboChild
     */
    public function setAdultRack(string $adultRack): OrderComboChild {
        $this->adultRack = $adultRack;
        return $this;
    }

    /**
     * Adult net
     *
     * @var string
     *
     * @ORM\Column(name="adult_net", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $adultNet;

    /**
     * @return string
     */
    public function getAdultNet(): string {
        return $this->adultNet;
    }

    /**
     * @param string $adultNet
     *
     * @return OrderComboChild
     */
    public function setAdultNet(string $adultNet): OrderComboChild {
        $this->adultNet = $adultNet;
        return $this;
    }

    /**
     * Child rack
     *
     * @var string
     *
     * @ORM\Column(name="child_rack", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $childRack;

    /**
     * @return string
     */
    public function getChildRack(): string {
        return $this->childRack;
    }

    /**
     * @param string $childRack
     *
     * @return OrderComboChild
     */
    public function setChildRack(string $childRack): OrderComboChild {
        $this->childRack = $childRack;
        return $this;
    }

    /**
     * Child net
     *
     * @var string
     *
     * @ORM\Column(name="child_net", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $childNet;

    /**
     * @return string
     */
    public function getChildNet(): string {
        return $this->childNet;
    }

    /**
     * @param string $childNet
     *
     * @return OrderComboChild
     */
    public function setChildNet(string $childNet): OrderComboChild {
        $this->childNet = $childNet;
        return $this;
    }

    /**
     * Child net
     *
     * @var string
     *
     * @ORM\Column(name="tax_price", nullable=false, type="decimal", precision=10, scale=2)
     */
    private $taxPrice;

    /**
     * @return string
     */
    public function getTaxPrice(): string {
        return $this->taxPrice;
    }

    /**
     * @param string $taxPrice
     *
     * @return OrderComboChild
     */
    public function setTaxPrice(string $taxPrice): OrderComboChild {
        $this->taxPrice = $taxPrice;
        return $this;
    }

    public function getSubTotal(): Money {
        $money = (new Money($this->getAdultRack()))->multiply($this->getOrderItem()->getAdultCount());
        $money = $money->add(
            (new Money($this->getChildRack()))->multiply($this->getOrderItem()->getChildCount())
        );

        return $money;
    }

    public function getGrandTotal(): Money {
        return $this->getSubTotal()->addStr($this->getTaxPrice());
    }
}