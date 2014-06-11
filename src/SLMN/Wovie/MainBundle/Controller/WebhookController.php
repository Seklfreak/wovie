<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function stripeAction()
    {
        $response = new Response();
        $input = @file_get_contents('php://input');

        $event_json = json_decode($input);
        $event = \Stripe_Event::retrieve($event_json->id);

        switch ($event['type'])
        {
            case 'invoice.created':
                $invoice = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $customer = $stripeCustomersRepo->findOneByCustomerId($invoice->customer);
                if ($customer)
                {
                    $paidUntil = new \DateTime();
                    $paidUntil->setTimestamp($invoice['lines']['data'][0]['period']['end']);
                    $customer->setPaidUntil($paidUntil);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($customer);
                    $em->flush();
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
                break;
            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $customer = $stripeCustomersRepo->findOneByCustomerId($invoice->customer);
                if ($customer)
                {
                    $paidUntil = new \DateTime();
                    $paidUntil->setTimestamp($invoice['lines']['data'][0]['period']['start']);
                    $customer->setPaidUntil($paidUntil);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($customer);
                    $em->flush();
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
                break;
            case 'customer.updated':
                $customer = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByCustomerId($customer->id);
                if ($stripeCustomer)
                {
                    $stripeCustomer->setDelinquent($customer->delinquent);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($stripeCustomer);
                    $em->flush();
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
                break;
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $customer = $stripeCustomersRepo->findOneByCustomerId($subscription->customer);
                if ($customer)
                {
                    /*
                    $followsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                    $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                    $profilesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                    $userOptionRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                    $viewRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                    // TODO: Remove all
                    $em = $this->getDoctrine()->getManager();
                    $em->remove($customer);
                    $em->remove($customer->getUser());
                    $em->flush();
                    */
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
            case 'charge.succeeded':
            case 'charge.failed':
                $charge = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByCustomerId($charge->customer);
                if ($stripeCustomer)
                {
                    $stripeCustomer->setChargeFailureMessage($charge->failure_code); // TODO: Translate
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($stripeCustomer);
                    $em->flush();
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
                break;
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByCustomerId($subscription->customer);
                if ($stripeCustomer)
                {
                    $paidUntil = new \DateTime();
                    $paidUntil->setTimestamp($subscription->current_period_end);
                    $stripeCustomer->setPaidUntil($paidUntil);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($stripeCustomer);
                    $em->flush();
                }
                else
                {
                    $response->setContent('Customer not found!');
                    $response->setStatusCode(500);
                }
                break;
            // TODO: customer.subscription.trial_will_end -> three days before trial ends
            // TODO: invoice.payment_succeeded-> send subscription receipt
            //          -> if stripe_invoice.closed and stripe_invoice.total == 0 -> trial invoice, dont send an email
            default:
                break;
        }
        return $response;
    }
} 