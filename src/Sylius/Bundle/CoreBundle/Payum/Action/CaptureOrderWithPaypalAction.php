<?php
namespace Sylius\Bundle\CoreBundle\Payum\Action;

use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Request\SecuredCaptureRequest;
use Sylius\Bundle\CoreBundle\Model\Order;
use Sylius\Bundle\CoreBundle\Model\PaypalPaymentDetails;

class CaptureOrderWithPaypalAction extends PaymentAwareAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request SecuredCaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Order $order */
        $order = $request->getModel();

        $paymentDetails = new PaypalPaymentDetails;
        $paymentDetails->setReturnurl($request->getToken()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getToken()->getTargetUrl());
        $paymentDetails->setPaymentrequestCurrencycode(0, $order->getCurrency());
        // I do not now why 0.87$ become 8700 here
        $paymentDetails->setPaymentrequestAmt(0,  number_format($order->getTotal() / 10000, 2));
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
            $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof Order &&
            $request->getModel()->getDetails() === null
        ;
    }
}