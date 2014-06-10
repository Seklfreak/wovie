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
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $customer = $stripeCustomersRepo->findOneByCustomerId($event->customer);
                if ($customer)
                {
                    $customer->setPaidUntil(new \DateTime($event->lines[0]->data[0]->period[0]->end));
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
            default:
                break;
        }
        return $response;
    }
} 