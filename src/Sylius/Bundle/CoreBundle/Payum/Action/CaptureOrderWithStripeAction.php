<?php
namespace Sylius\Bundle\CoreBundle\Payum\Action;

use Payum\Request\SecuredCaptureRequest;
use Sylius\Bundle\CoreBundle\Model\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Payum\Action\PaymentAwareAction;
use Payum\Registry\RegistryInterface;
use Payum\Exception\RequestNotSupportedException;
use Payum\Bundle\PayumBundle\Request\ResponseInteractiveRequest;
use Symfony\Component\Validator\Constraints\Range;

class CaptureOrderWithStripeAction extends PaymentAwareAction
{
    /**
     * @var RegistryInterface
     */
    protected $payum;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->payum = $container->get('payum');
        $this->formFactory = $container->get('form.factory');
        $this->templating = $container->get('templating');
        $this->request = $container->get('request');
    }

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request SecuredCaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $form = $this->createPurchaseForm();
        $form->handleRequest($this->request);
        if ($form->isValid()) {
            $request->getModel()->setDetails($form->getData());

            $this->payment->execute($request);

            return;
        }

        throw new ResponseInteractiveRequest(new Response(
            $this->templating->render('SyliusCoreBundle:StripePurchase:creditCard.html.twig', array(
                'form' => $form->createView()
            ))
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof Order &&
            $request->getModel()->getDetails() === null
        ;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        $creditCardBuilder = $this->formFactory->createNamedBuilder('card')
            ->add('number', null, array(
                'data' => '4242424242424242',
                'property_path' => '[number]'
            ))
            ->add('expiryMonth', null, array(
                'data' => '6',
                'property_path' => '[expiryMonth]'
            ))
            ->add('expiryYear', null, array(
                'data' => '2016',
                'property_path' => '[expiryYear]'
            ))
            ->add('cvv', null, array(
                'data' => '123',
                'property_path' => '[cvv]'
            ))
        ;

        $builder =  $this->formFactory->createBuilder()
            ->add('amount', null, array(
                'data' => 1.23,
                'property_path' => '[amount]',
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array(
                'data' => 'USD',
                'property_path' => '[currency]',
            ))
            ->add($creditCardBuilder)
        ;

        return $builder->getForm();
    }
}