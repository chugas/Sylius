<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Checkout\Step;

use Sylius\Bundle\CoreBundle\Model\Order;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\SalesBundle\Model\OrderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Final checkout step.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class FinalizeStep extends CheckoutStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $order = $this->createOrder($context);

        return $this->renderStep($context, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        /** @var Order $order */
        $order = $this->createOrder($context);

        $this->saveOrder($order);
        $this->getCartProvider()->abandonCart();

        $captureToken = $this->get('payum.token_manager')->createTokenForCaptureRoute(
            $order->getPayment()->getMethod()->getGateway(),
            $order,
            'sylius_homepage'
        );

        return $this->redirect($captureToken->getTargetUrl());
    }

    private function renderStep(ProcessContextInterface $context, OrderInterface $order)
    {
        return $this->render('SyliusWebBundle:Frontend/Checkout/Step:finalize.html.twig', array(
            'context' => $context,
            'order'   => $order
        ));
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
