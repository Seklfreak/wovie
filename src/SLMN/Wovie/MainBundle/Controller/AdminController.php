<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function emailPreviewAction($template, $inlined) {
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