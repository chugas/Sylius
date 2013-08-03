<?php
namespace Sylius\Bundle\CoreBundle\Model;

use Payum\Paypal\ExpressCheckout\Nvp\Model\PaymentDetails;

class PaypalPaymentDetails extends PaymentDetails
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}