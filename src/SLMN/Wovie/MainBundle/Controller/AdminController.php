<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Symfony\Component\HttpFoundation\Response;

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

    public function broadcastsAction()
    {
        return $this->render('SLMNWovieMainBundle:html/user/admin:tab-broadcasts.html.twig');
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
