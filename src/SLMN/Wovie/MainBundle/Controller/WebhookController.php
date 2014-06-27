<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\Entity\Invoice;
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
                    $stripeCustomer->setChargeFailureMessage($charge->failure_message); // TODO: Translate
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
                    $stripeCustomer->setCancelled($subscription->cancel_at_period_end);
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
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $customer = $stripeCustomersRepo->findOneByCustomerId($invoice->customer);
                if ($customer)
                {
                    $invoiceDb = new Invoice();
                    $invoiceDb->setUser($customer->getUser());
                    $invoiceDb->setAmount($invoice->total);
                    $invoiceDate = new \DateTime();
                    $invoiceDate->setTimestamp($invoice['date']);
                    $invoiceDb->setDate($invoiceDate);
                    $invoiceDb->setInvoiceId($invoice->id);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($invoiceDb);
                    $em->flush();
                    if ($invoiceDb->getAmount() > 0)
                    {
                        $pdfDocs = $this->get('pdf_docs');
                        $pdfDocs->generateReceipt($invoiceDb);
                        $pdfBody = $pdfDocs->getBody();
                        $attachment = \Swift_Attachment::newInstance($pdfBody, 'receipt-'.$invoiceDb->getDate()->format('Y-m-d').'.pdf', 'application/pdf');

                        $this->get('templateMailer')->send(
                            $invoiceDb->getUser()->getEmail(),
                            'Receipt from '.$invoiceDb->getDate()->format('Y-m-d'),
                            'SLMNWovieMainBundle:email:receipt.html.twig',
                            array(
                                'dbUser' => $invoiceDb->getUser()
                            ),
                            $attachment,
                            $this->container->getParameter('slmn_wovie_mainbundle.billing-email')
                        );
                    }
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