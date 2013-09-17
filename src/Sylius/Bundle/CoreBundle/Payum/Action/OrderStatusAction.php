<?php
namespace Sylius\Bundle\CoreBundle\Payum\Action;

use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Request\StatusRequestInterface;
use Sylius\Bundle\CoreBundle\Model\Order;

class OrderStatusAction extends PaymentAwareAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request StatusRequestInterface */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Order $order */
        $order = $request->getModel();

        if ($order->getDetails()) {
            $request->setModel($order->getDetails());

            $this->payment->execute($request);

            $request->setModel($order);
        } else {
            $request->markNew();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof StatusRequestInterface &&
            $request->getModel() instanceof Order
        ;
    }
}