<?php
namespace Sylius\Bundle\CoreBundle\Checkout\Step;

use Payum\Bundle\PayumBundle\Service\TokenManager;
use Payum\Registry\RegistryInterface;
use Payum\Request\BinaryMaskStatusRequest;
use Sylius\Bundle\CoreBundle\Model\OrderInterface;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DoPaymentStep extends CheckoutStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $order = $this->createOrder($context);
        $this->saveOrder($order);

        $captureToken = $this->getPayumTokenManager()->createTokenForCaptureRoute(
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
        $token = $this->getPayumTokenManager()->getTokenFromRequest($context->getRequest());
        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);

        /** @var OrderInterface $order */
        $order = $status->getModel();

        //guard
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

        //prevent cheating on refreshing this page.
        $this->getPayumTokenManager()->deleteToken($token);

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
     * @return TokenManager
     */
    protected function getPayumTokenManager()
    {
        return $this->get('payum.token_manager');
    }

    /**
     * Create order based on the checkout context.
     *
     * @param ProcessContextInterface $context
     *
     * @return OrderInterface
     */
    private function createOrder(ProcessContextInterface $context)
    {
        $order = $this->getCurrentCart();

        $order->setUser($this->getUser());

        $order->calculateTotal();
        $this->get('event_dispatcher')->dispatch('sylius.order.pre_create', new GenericEvent($order));
        $order->calculateTotal();

        return $order;
    }

    /**
     * Save given order.
     *
     * @param OrderInterface $order
     */
    protected function saveOrder(OrderInterface $order)
    {
        $manager = $this->get('sylius.manager.order');

        $order->complete();

        $manager->persist($order);
        $manager->flush($order);

        $this->get('event_dispatcher')->dispatch('sylius.order.post_create', new GenericEvent($order));
    }
}