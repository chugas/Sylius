<?php
namespace Sylius\Bundle\CoreBundle\Payum\Action;

use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Request\CaptureTokenizedDetailsRequest;
use Sylius\Bundle\CoreBundle\Model\Order;
use Sylius\Bundle\CoreBundle\Model\PaypalPaymentDetails;

class CaptureOrderWithPaypalAction extends PaymentAwareAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CaptureTokenizedDetailsRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Order $order */
        $order = $request->getModel();

        $paymentDetails = new PaypalPaymentDetails;
        $paymentDetails->setReturnurl($request->getTokenizedDetails()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getTokenizedDetails()->getTargetUrl());
        $paymentDetails->setPaymentrequestCurrencycode(0, $order->getCurrency());
        $paymentDetails->setPaymentrequestAmt(0,  $order->getTotal());
        $paymentDetails->setInvnum($order->getNumber());

        $order->setDetails($paymentDetails);

        $this->payment->execute($request);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CaptureTokenizedDetailsRequest &&
            $request->getModel() instanceof Order &&
            $request->getModel()->getDetails() === null
        ;
    }
}