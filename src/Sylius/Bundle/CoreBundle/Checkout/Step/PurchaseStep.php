<?php
namespace Sylius\Bundle\CoreBundle\Checkout\Step;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Registry\RegistryInterface;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Security\HttpRequestVerifierInterface;
use Sylius\Bundle\CoreBundle\Model\OrderInterface;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PurchaseStep extends CheckoutStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $order = $this->getCurrentCart();

        $captureToken = $this->getTokenFactory()->createCaptureToken(
            $order->getPayment()->getMethod()->getGateway(),
            $order,
            'sylius_checkout_forward',
            array('stepName' => $this->getName())
        );

        return new RedirectResponse($captureToken->getTargetUrl());
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $token = $this->getHttpRequestVerifier()->verify($this->getRequest());
        $this->getHttpRequestVerifier()->invalidate($token);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);

        /** @var OrderInterface $order */
        $order = $status->getModel();

        if (false == $order instanceof OrderInterface) {
            throw new \RuntimeException(sprintf('Expected order to be set as model but it is %s', get_class($order)));
        }

        $translator = $this->get('translator');
        if ($status->isSuccess()) {
            //do some extra stuff here. an event or set status to order for example

            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.success', array(), 'flashes'));
        } elseif ($status->isPending()) {
            $this->get('session')->getFlashBag()->add('notice', $translator->trans('sylius.checkout.pending', array(), 'flashes'));
        } elseif ($status->isCanceled()) {
            $this->get('session')->getFlashBag()->add('notice', $translator->trans('sylius.checkout.canceled', array(), 'flashes'));
        } elseif ($status->isExpired()) {
            $this->get('session')->getFlashBag()->add('notice', $translator->trans('sylius.checkout.expired', array(), 'flashes'));
        } elseif ($status->isSuspended()) {
            $this->get('session')->getFlashBag()->add('notice', $translator->trans('sylius.checkout.suspended', array(), 'flashes'));
        } elseif ($status->isFailed()) {
            $this->get('session')->getFlashBag()->add('error', $translator->trans('sylius.checkout.failed', array(), 'flashes'));
        } else {
            $this->get('session')->getFlashBag()->add('error', $translator->trans('sylius.checkout.unknown', array(), 'flashes'));
        }

        $this->getCartProvider()->abandonCart();

        return $this->complete();
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }

    /**
     * @return HttpRequestVerifierInterface
     */
    protected function getHttpRequestVerifier()
    {
        return $this->get('payum.security.http_request_verifier');
    }
}