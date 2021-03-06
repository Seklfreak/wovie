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
                    $em = $this->getDoctrine()->getManager();
                    $activitiesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Activity');
                    $followsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Follow');
                    $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
                    $profilesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Profile');
                    $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
                    $viewsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:View');
                    $invoicesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Invoice');
                    $pendingUserActivationsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:PendingUserActivation');

                    $batchSize = 20;

                    // Remove activity
                    $i = 0;
                    $q = $activitiesRepo->createQueryBuilder('activity')
                        ->where('activity.user = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // Remove follows
                    $i = 0;
                    $q = $followsRepo->createQueryBuilder('follow')
                        ->where('follow.user = :user')
                        ->orWhere('follow.follow = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // Remove invoices
                    $i = 0;
                    $q = $invoicesRepo->createQueryBuilder('invoice')
                        ->where('invoice.user = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // Remove medias
                    $i = 0;
                    $q = $mediasRepo->createQueryBuilder('media')
                        ->where('media.createdBy = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        // Remove views for media
                        $iView = 0;
                        $qView = $viewsRepo->createQueryBuilder('view')
                                 ->where('view.media = :media')
                                 ->setParameters(array(
                                     'media' => $row[0]
                                 ))
                                 ->getQuery();
                        $iterableResultView = $qView->iterate();
                        while (($rowView = $iterableResultView->next()) !== false)
                        {
                            $em->remove($rowView[0]);
                            if (($iView % $batchSize) == 0)
                            {
                                $em->flush();
                                $em->detach($rowView[0]);
                            }
                            ++$iView;
                        }
                        $em->flush();
                        // TODO: Remove custom covers

                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // Remove pending user activiations
                    $i = 0;
                    $q = $pendingUserActivationsRepo->createQueryBuilder('pua')
                        ->where('pua.user = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // remove profiles
                    $i = 0;
                    $q = $profilesRepo->createQueryBuilder('profile')
                        ->where('profile.user = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    // remove user options
                    $i = 0;
                    $q = $userOptionsRepo->createQueryBuilder('userOption')
                        ->where('userOption.createdBy = :user')
                        ->setParameters(array(
                            'user' => $customer->getUser()
                        ))
                        ->getQuery();
                    $iterableResult = $q->iterate();
                    while (($row = $iterableResult->next()) !== false)
                    {
                        $em->remove($row[0]);
                        if (($i % $batchSize) == 0)
                        {
                            $em->flush();
                            $em->clear();
                        }
                        ++$i;
                    }

                    $customer = $stripeCustomersRepo->findOneByCustomerId($subscription->customer);
                    $em->remove($customer);
                    $em->flush();
                    $em->remove($customer->getUser());
                    $em->flush();
                    $customer->delete();
                }
                else
                {
                    $response->setContent('Customer not found!');
                }
                break;
            case 'charge.succeeded':
            case 'charge.failed':
                $charge = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByCustomerId($charge->customer);
                if ($stripeCustomer)
                {
                    $stripeCustomer->setChargeFailureMessage($charge->failure_message);
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
            case 'customer.subscription.trial_will_end':
                $subscription = $event->data->object;
                $stripeCustomersRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByCustomerId($subscription->customer);
                if ($stripeCustomer)
                {
                    $this->get('templateMailer')->send(
                        $stripeCustomer->getUser()->getEmail(),
                        'Your WOVIE trial will end soon!',
                        'SLMNWovieMainBundle:email:trialWillEndSoon.html.twig',
                        array(
                            'urlSettingsBilling' => $this->generateUrl('slmn_wovie_user_settings_billing'),
                            'urlSettingsAccount' => $this->generateUrl('slmn_wovie_user_settings_profile')
                        )
                    );
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