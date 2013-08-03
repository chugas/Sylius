<?php
namespace Sylius\Bundle\CoreBundle\Model;

class StripePaymentDetails extends \ArrayObject
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}