<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use SLMN\Wovie\MainBundle\Entity\Broadcast;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    public function dashboardAction()
    {
        $usersRepo = $this->getDoctrine()
            ->getRepository('SeklMainUserBundle:User');
        $mediasRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Media');
        $viewsRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:View');

        $totalUsers = $usersRepo->createQueryBuilder('object')
                ->select('count(object.id)')
                ->getQuery()->getSingleScalarResult();
        $totalMedias = $mediasRepo->createQueryBuilder('object')
                ->select('count(object.id)')
                ->getQuery()->getSingleScalarResult();
        $totalViews = $viewsRepo->createQueryBuilder('object')
                ->select('count(object.id)')
                ->getQuery()->getSingleScalarResult();

        return $this->render(
            'SLMNWovieMainBundle:html/user/admin:tab-dashboard.html.twig',
            array(
                'totalUsers' => $totalUsers,
                'totalMedias' => $totalMedias,
                'totalViews' => $totalViews
                )
            );
    }

    public function broadcastsAction(Request $request)
    {
        $broadcastsRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Broadcast');
        $em = $this->getDoctrine()->getManager();

        $broadcasts = $broadcastsRepo->findAll();

        $newBroadcast = new Broadcast();
        $newBroadcastForm = $this->createForm('broadcast', $newBroadcast);
        $newBroadcastForm->handleRequest($request);
        if ($newBroadcastForm->isValid())
        {
            $newBroadcast->setCreatedAt(new \DateTime());

            $em->persist($newBroadcast);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Broadcast added.');
            return $this->redirect($this->generateUrl('slmn_wovie_admin_broadcasts'));
        }


        return $this->render(
            'SLMNWovieMainBundle:html/user/admin:tab-broadcasts.html.twig',
            array(
                'broadcasts' => $broadcasts,
                'newBroadcastForm' => $newBroadcastForm->createView()
                )
            );
    }

    public function editBroadcastAction(Request $request, $broadcastId)
    {
        $broadcastsRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Broadcast');
        $em = $this->getDoctrine()->getManager();

        $editBroadcast = $broadcastsRepo->findOneById($broadcastId);

        if (!$editBroadcast)
        {
            $this->get('session')->getFlashBag()->add('error', 'Broadcast not found.');
            return $this->redirect($this->generateUrl('slmn_wovie_admin_broadcasts'));
        }

        $editBroadcastForm = $this->createForm('broadcast', $editBroadcast);
        $editBroadcastForm->handleRequest($request);
        if ($editBroadcastForm->isValid())
        {
            $em->persist($editBroadcast);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Broadcast successfully edited.');
            return $this->redirect($this->generateUrl('slmn_wovie_admin_broadcasts'));
        }


        return $this->render(
            'SLMNWovieMainBundle:html/user/admin:tab-broadcasts-edit.html.twig',
            array(
                'editBroadcastForm' => $editBroadcastForm->createView()
                )
            );
    }

    public function deleteBroadcastAjaxAction(Request $request)
    {
        $response = new JsonResponse();
        if (($broadcastId=intval($request->get('broadcast_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $broadcastRepo = $em->getRepository('SLMNWovieMainBundle:Broadcast');
            $broadcast = $broadcastRepo->findOneById($broadcastId);
            if ($broadcast != null)
            {
                $em->remove($broadcast);
                $em->flush();
                $response->setData(array(
                    'status' => 'success'
                ));
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function emailTemplatesAction()
    {
        // get template files
        $bundles = $this->get('kernel')->getBundles();
        $templates = scandir($bundles['SLMNWovieMainBundle']->getPath().'/Resources/views/email/');

        return $this->render(
            'SLMNWovieMainBundle:html/user/admin:tab-emailTemplates.html.twig',
            array('templates' => $templates)
            );
    }

    public function emailPreviewAction($template, $inlined)
    {
        if ($inlined == false) {
            return $this->render('SLMNWovieMainBundle:email:'.$template.'.html.twig', array(
            ));
        } else {
            $engine = $this->container->get('templating');
            $normalMail = $engine->render('SLMNWovieMainBundle:email:'.$template.'.html.twig');

            $inliner = new CssToInlineStyles($normalMail);
            $inliner->setUseInlineStylesBlock(true);
            $inliner->setStripOriginalStyleTags(true);
            return new Response($inliner->convert());
        }
    }
}
