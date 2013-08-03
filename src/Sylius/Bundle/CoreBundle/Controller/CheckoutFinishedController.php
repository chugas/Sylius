<?php
namespace Sylius\Bundle\CoreBundle\Controller;

use Payum\Bundle\PayumBundle\Service\TokenManager;
use Payum\Registry\RegistryInterface;
use Payum\Request\BinaryMaskStatusRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CheckoutFinishedController extends Controller
{
    public function finishedAction(Request $request)
    {
        $token = $this->getPayumTokenManager()->getTokenFromRequest($request);
        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new BinaryMaskStatusRequest($token);

        $payment->execute($status);

        $translator = $this->get('translator');
        if ($status->isSuccess()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.success', array(), 'flashes'));

            //do an event
            //or set status to order
        } elseif ($status->isPending()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.pending', array(), 'flashes'));
        } elseif ($status->isCanceled()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.canceled', array(), 'flashes'));
        } elseif ($status->isExpired()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.expired', array(), 'flashes'));
        } elseif ($status->isSuspended()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.suspended', array(), 'flashes'));
        } elseif ($status->isFailed()) {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.failed', array(), 'flashes'));
        } else {
            $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.checkout.unknown', array(), 'flashes'));
        }

        //prevent cheating on refreshing this page.
        $this->getPayumTokenManager()->deleteToken($token);

        return $this->redirect($this->generateUrl('sylius_homepage'));
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
}