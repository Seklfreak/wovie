<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    public function externalCoverAction($id)
    {
        // TODO: Image cache
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://ia.media-imdb.com/images/M/'.$id.'.jpg');
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->container->get('kernel')->getEnvironment());
        $image = curl_exec($curl_handle);
        curl_close($curl_handle);

        $response = new Response($image);
        $response->headers->set('Content-Type', 'image/jpeg');
        return $response;
    }
} 