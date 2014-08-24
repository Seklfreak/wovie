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
    private $logger;

    public function __construct(
        \Doctrine\ORM\EntityManager $em,
        \Symfony\Component\Security\Core\SecurityContext $context,
        $session,
        $router,
        $apiKey,
        $plan,
        $logger
    )
    {
        $this->context = $context;
        $this->em = $em;
        $this->session = $session;
        $this->router = $router;
        $this->apiKey = $apiKey;
        $this->plan = $plan;
        $this->logger = $logger;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        \Stripe::setApiKey($this->apiKey);

        if (!$event->isMasterRequest()) {
            return;
        }

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
                    $this->logger->error('BillingListener - Stripe error: '.$e->getMessage());
                    $this->session->getFlashBag()->add('error', 'Error: '.$e->getMessage());
                }
            }
            else
            {
                // Check if subscription is valid
                if ($customer->getDelinquent() == true || new \DateTime() > $customer->getPaidUntil())
                {
                    $routeWhitelist = array(
                        'slmn_wovie_public_imprint',
                        'slmn_wovie_user_settings_general',
                        'slmn_wovie_user_settings_profile',
                        'slmn_wovie_user_settings_billing',
                        'slmn_wovie_user_settings_account_cancel',
                        'slmn_wovie_user_feedback'
                    );

                    if (
                        in_array($event->getRequest()->get('_route'), $routeWhitelist)
                        || $this->context->isGranted('ROLE_PREVIOUS_ADMIN')
                        )
                    {
                        if ($event->getRequest()->getMethod() == 'GET')
                        {
                            $this->session->getFlashBag()->add('error', 'You subscription or trial ended please update your billing information.');
                        }
                    }
                    else
                    {
                        // If route not in whitelist, redirect to billing settings
                        $url = $this->router->generate('slmn_wovie_user_settings_billing');
                        $response = new RedirectResponse($url);
                        $event->setResponse($response);
                    }
                }
            }
        }
        return;
    }
}
