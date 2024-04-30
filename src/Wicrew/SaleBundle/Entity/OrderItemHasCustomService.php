<?php

namespace App\Wicrew\SaleBundle\Entity;

use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\AddonBundle\Entity\ExtraOption;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use App\Wicrew\PartnerBundle\Entity\Partner;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrderItemHasCustomService
 *
 * @ORM\Table(name="OrderItemHasCustomService", uniqueConstraints={@ORM\UniqueConstraint(name="id_UNIQUE", columns={"id"})}, indexes={
 *     @ORM\Index(name="fk_OrderItemHasCustomService_OrderItem_idx", columns={"order_item_id"})
 * })
 * @ORM\Entity
 */
class OrderItemHasCustomService extends BaseEntity {
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
     * Order item
     *
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="App\Wicrew\SaleBundle\Entity\OrderItem", inversedBy="Extras", cascade={"persist"})
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $orderItem;

    /**
     * Label
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * Rack price
     *
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="rack_price", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $rackPrice;

    /**
     * Get ID
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return OrderItemHasCustomService
     */
    public function setId($id): OrderItemHasCustomService {
        $this->id = $id;
        return $this;
    }

    /**
     * Get order Item
     *
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem {
        return $this->orderItem;
    }

    /**
     * Set order item
     *
     * @param OrderItem $orderItem
     *
     * @return OrderItemHasCustomService
     */
    public function setOrderItem(OrderItem $orderItem): OrderItemHasCustomService {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return OrderItemHasCustomService
     */
    public function setLabel($label): OrderItemHasCustomService {
        $this->label = $label;
        return $this;
    }

    /**
     * Get rack price
     *
     * @return string
     */
    public function getRackPrice() {
        return $this->rackPrice;
    }

    /**
     * Set rack price
     *
     * @param string $rackPrice
     *
     * @return OrderItemHasCustomService
     */
    public function setRackPrice($rackPrice): OrderItemHasCustomService {
        $this->rackPrice = $rackPrice;
        return $this;
    }

}
