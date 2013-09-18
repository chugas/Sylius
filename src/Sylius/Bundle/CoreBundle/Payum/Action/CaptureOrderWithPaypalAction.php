<?php
namespace Sylius\Bundle\CoreBundle\Payum\Action;

use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Request\SecuredCaptureRequest;
use Sylius\Bundle\CoreBundle\Model\Order;
use Sylius\Bundle\CoreBundle\Model\PaypalPaymentDetails;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

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

        /*$paymentDetails = new PaypalPaymentDetails;
        $paymentDetails->setReturnurl($request->getToken()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getToken()->getTargetUrl());
        $paymentDetails->setPaymentrequestCurrencycode(0, $order->getCurrency());
        // I do not now why 0.87$ become 8700 here
        $paymentDetails->setPaymentrequestAmt(0,  number_format($order->getTotal() / 10000, 2));
        $paymentDetails->setInvnum($order->getNumber());*/

        $paymentDetails = new PaypalPaymentDetails();
        $paymentDetails->setPaymentrequestCurrencycode(0, $order->getCurrency());
        $paymentDetails->setPaymentrequestAmt(0,  (float)number_format($order->getItemsTotal() / 100, 2));        
        $paymentDetails->setNoshipping(Api::NOSHIPPING_NOT_DISPLAY_ADDRESS);
        $paymentDetails->setReqconfirmshipping(Api::REQCONFIRMSHIPPING_NOT_REQUIRED);
        $paymentDetails->setLPaymentrequestItemcategory(0, 0, Api::PAYMENTREQUEST_ITERMCATEGORY_DIGITAL);
        $items = $order->getItems();
        $i = 0;
        foreach($items as $item){
          $paymentDetails->setLPaymentrequestAmt(0, $i, (float)number_format($item->getTotal() / 100, 2));
          $paymentDetails->setLPaymentrequestQty(0, $i, $item->getQuantity());
          $paymentDetails->setLPaymentrequestName(0, $i, $item->getProduct()->getName());
          $paymentDetails->setLPaymentrequestDesc(0, $i, $item->getProduct()->getShortDescription());
          $i++;
        }
        $paymentDetails->setReturnurl($request->getToken()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getToken()->getTargetUrl());
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