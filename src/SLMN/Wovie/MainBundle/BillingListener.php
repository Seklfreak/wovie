<?php

namespace SLMN\Wovie\MainBundle;

use SLMN\Wovie\MainBundle\Entity\StripeCustomer;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BillingListener
{
    private $context;
    private $em;
    private $session;
    private $router;
    private $apiKey;
    private $plan;

    public function __construct(
        \Doctrine\ORM\EntityManager $em,
        \Symfony\Component\Security\Core\SecurityContext $context,
        $session,
        $router,
        $apiKey,
        $plan
    )
    {
        $this->context = $context;
        $this->em = $em;
        $this->session = $session;
        $this->router = $router;
        $this->apiKey = $apiKey;
        $this->plan = $plan;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        \Stripe::setApiKey($this->apiKey);

        if (!$event->isMasterRequest()) {
            return;
        }

        //$usersRepo = $this->em->getRepository('SeklMainUserBundle:User');
        //$customersRepo = $this->em->getRepository('SLMNWovieMainBundle:StripeCustomer');

        $isAuthenticatedRemembered = false;
        try
        {
            $isAuthenticatedRemembered = $this->context->isGranted('IS_AUTHENTICATED_REMEMBERED');
        }
        catch (\Exception $e)
        {
            return;
        }
        if ($isAuthenticatedRemembered)
        {
            $customersRepo = $this->em->getRepository('SLMNWovieMainBundle:StripeCustomer');
            $customer = $customersRepo->findOneByUser($this->context->getToken()->getUser());
            // Create User (without credit card)
            if (!$customer)
            {
                try
                {
                    $customer = \Stripe_Customer::create(array(
                            'plan' => $this->plan,
                            'email' => $this->context->getToken()->getUser()->getEmail()
                        )
                    );

                    $stripeCustomer = new StripeCustomer();
                    $stripeCustomer->setUser($this->context->getToken()->getUser());
                    $stripeCustomer->setCustomerId($customer['id']);
                    $paidUntil = new \DateTime();
                    $paidUntil->setTimestamp($customer['subscriptions']['data'][0]['current_period_end']);
                    $stripeCustomer->setPaidUntil($paidUntil);
                    $this->em->persist($stripeCustomer);
                    $this->em->flush();
                }
                catch (\Exception $e)
                {
                    // TODO: Log this error
                    $this->session->getFlashBag()->add('error', 'Error: '.$e->getMessage());
                }
            }
            else
            {
                // Check if subscription is valid
                if ($customer->getDelinquent() == true || new \DateTime() > $customer->getPaidUntil())
                {
                    $route = 'slmn_wovie_user_settings_billing';

                    if ($route === $event->getRequest()->get('_route'))
                    {
                        $this->session->getFlashBag()->add('error', 'You subscription or trial ended please update your billing information.');
                        return;
                    }

                    $url = $this->router->generate($route);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
            }
        }
        return;
        //$this->context->getToken()->getUser())
        /*
        $route = 'route_name';

        if ($route === $event->getRequest()->get('_route')) {
            return;
        }

        $url = $this->router->generate($route);
        $response = new RedirectResponse($url);
        $event->setResponse($response);
        */
    }
}
