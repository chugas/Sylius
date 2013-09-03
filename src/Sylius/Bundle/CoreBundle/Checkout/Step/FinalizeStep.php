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
        return $this->complete();
    }

    private function renderStep(ProcessContextInterface $context, OrderInterface $order)
    {
        return $this->render('SyliusWebBundle:Frontend/Checkout/Step:finalize.html.twig', array(
            'context' => $context,
            'order'   => $order
        ));
    }
}
