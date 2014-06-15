<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends Controller
{
    public function mainAction()
    {
        $urls = array();

        $urls[] = array(
            'loc' => $this->get('router')->generate('login', array(), true),
            'changefreq' => 'monthly',
            'priority' => '0.8'
        );
        $urls[] = array(
            'loc' => $this->get('router')->generate('slmn_wovie_public_index', array(), true),
            'changefreq' => 'monthly',
            'priority' => '1'
        );

        return $this->render('SLMNWovieMainBundle:html/sitemap:main.xml.twig', array(
            'urls' => $urls
        ));

        /*
        foreach($languages as $lang) {
            $urls[] = array('loc' => $this->get('router')->generate('home_contact', array('_locale' => $lang)), 'changefreq' => 'monthly', 'priority' => '0.3');
        }
        */

        /*
        $urls[] = array('loc' => $this->get('router')->generate('home_product_overview', array('_locale' => 'en')), 'changefreq' => 'weekly', 'priority' => '0.7');
        // service
        foreach ($em->getRepository('AcmeSampleStoreBundle:Product')->findAll() as $product) {
            $urls[] = array('loc' => $this->get('router')->generate('home_product_detail',
                    array('productSlug' => $product->getSlug())), 'priority' => '0.5');
        }
        */
    }
}