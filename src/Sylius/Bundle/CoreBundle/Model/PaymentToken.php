<?php
namespace Sylius\Bundle\CoreBundle\Model;

use Payum\Model\Token;

class PaymentToken extends Token
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