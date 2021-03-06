<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Payum\Model\DetailsAggregateInterface;
use Payum\Model\DetailsAwareInterface;
use Sylius\Bundle\AddressingBundle\Model\AddressInterface;
use Sylius\Bundle\CartBundle\Model\Cart;
use Sylius\Bundle\PaymentsBundle\Model\PaymentInterface;
use Sylius\Bundle\SalesBundle\Model\AdjustmentInterface;

/**
 * Order entity.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class Order extends Cart implements OrderInterface, DetailsAwareInterface, DetailsAggregateInterface
{
    /**
     * User.
     *
     * @var UserInterface
     */
     protected $user;

    /**
     * Order shipping address.
     *
     * @var AddressInterface
     */
    protected $shippingAddress;

    /**
     * Order billing address.
     *
     * @var AddressInterface
     */
    protected $billingAddress;

    /**
     * Shipments for this order.
     *
     * @var Collection
     */
    protected $shipments;

    /**
     * Payment.
     *
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * Inventory units.
     *
     * @var Collection
     */
    protected $inventoryUnits;

    /**
     * Currency ISO code.
     *
     * @var string
     */
    protected $currency;

    /**
     * @var object
     */
    protected $paymentDetails;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->inventoryUnits = new ArrayCollection();
        $this->shipments = new ArrayCollection();
        $this->currency = 'USD'; // @todo: Temporary
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingAddress(AddressInterface $address)
    {
        $this->shippingAddress = $address;
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function setBillingAddress(AddressInterface $address)
    {
        $this->billingAddress = $address;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxTotal()
    {
        $taxTotal = 0;

        foreach ($this->getTaxAdjustments() as $adjustment) {
            $taxTotal += $adjustment->getAmount();
        }

        return $taxTotal;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxAdjustments()
    {
        return $this->adjustments->filter(function (AdjustmentInterface $adjustment) {
            return Order::TAX_ADJUSTMENT === $adjustment->getLabel();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function removeTaxAdjustments()
    {
        foreach ($this->getTaxAdjustments() as $adjustment) {
            $this->removeAdjustment($adjustment);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotionTotal()
    {
        $promotionTotal = 0;

        foreach ($this->getPromotionAdjustments() as $adjustment) {
            $promotionTotal += $adjustment->getAmount();
        }

        return $promotionTotal;
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotionAdjustments()
    {
        return $this->adjustments->filter(function (AdjustmentInterface $adjustment) {
            return Order::PROMOTION_ADJUSTMENT === $adjustment->getLabel();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function removePromotionAdjustments()
    {
        foreach ($this->getPromotionAdjustments() as $adjustment) {
            $this->removeAdjustment($adjustment);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTotal()
    {
        $shippingTotal = 0;

        foreach ($this->getShippingAdjustments() as $adjustment) {
            $shippingTotal += $adjustment->getAmount();
        }

        return $shippingTotal;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAdjustments()
    {
        return $this->adjustments->filter(function (AdjustmentInterface $adjustment) {
            return Order::SHIPPING_ADJUSTMENT === $adjustment->getLabel();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function removeShippingAdjustments()
    {
        foreach ($this->getShippingAdjustments() as $adjustment) {
            $this->removeAdjustment($adjustment);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * {@inheritdoc}
     */
    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInventoryUnits()
    {
        return $this->inventoryUnits;
    }

    /**
     * {@inheritdoc}
     */
    public function addInventoryUnit(InventoryUnitInterface $unit)
    {
        if (!$this->inventoryUnits->contains($unit)) {
            $unit->setOrder($this);
            $this->inventoryUnits->add($unit);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeInventoryUnit(InventoryUnitInterface $unit)
    {
        if ($this->inventoryUnits->contains($unit)) {
            $unit->setOrder(null);
            $this->inventoryUnits->removeElement($unit);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShipments()
    {
        return $this->shipments;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShipments()
    {
        return !$this->shipments->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function addShipment(ShipmentInterface $shipment)
    {
        if (!$this->hasShipment($shipment)) {
            $shipment->setOrder($this);
            $this->shipments->add($shipment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeShipment(ShipmentInterface $shipment)
    {
        if ($this->hasShipment($shipment)) {
            $shipment->setOrder(null);
            $this->shipments->removeElement($shipment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasShipment(ShipmentInterface $shipment)
    {
        return $this->shipments->contains($shipment);
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotionCoupon()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotionSubjectItemTotal()
    {
        return $this->getTotal();
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotionSubjectItemCount()
    {
        return $this->items->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Gets the last updated shipment of the order
     *
     * @return null|ShipmentInterface
     */
    public function getLastShipment()
    {
        $last = $this->shipments->count() ? $this->shipments->first() : null;

        foreach ($this->shipments as $shipment) {
            if ($shipment->getUpdatedAt() > $last->getUpdatedAt()) {
                $last = $shipment;
            }
        }

        return $last;
    }

    /**
     * Tells is the invoice of the order can be generated.
     *
     * @return Boolean
     */
    public function isInvoiceAvailable()
    {
        if (null !== $lastShipment = $this->getLastShipment()) {
            return (in_array(
                    $lastShipment->getState(),
                    array(ShipmentInterface::STATE_RETURNED, ShipmentInterface::STATE_SHIPPED))
            );
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDetails()
    {
        return $this->paymentDetails;
    }

    /**
     * {@inheritDoc}
     */
    public function setDetails($details)
    {
        $this->paymentDetails = $details;
    }
}
