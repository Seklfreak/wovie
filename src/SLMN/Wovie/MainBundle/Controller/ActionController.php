<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\MediaApi\MediaApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ActionController extends Controller
{
    public function searchExternalAction($query)
    {
        $mediaApi = new MediaApi();
        $result = $mediaApi->search($query, true);

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}