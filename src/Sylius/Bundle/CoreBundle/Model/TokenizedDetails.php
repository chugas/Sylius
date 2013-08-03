<?php
namespace Sylius\Bundle\CoreBundle\Model;

use Payum\Model\TokenizedDetails as BaseTokenizedDetails;

class TokenizedDetails extends BaseTokenizedDetails
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