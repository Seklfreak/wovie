<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionController extends Controller
{
    public function searchExternalAction(Request $request)
    {
        $query = trim($request->query->get('q'));

        $mediaApi = $this->get('media_api');
        $result = $mediaApi->search('(all (any name:"'.$query.'" alias:"'.$query.'") (any type:/film/film type:/tv/tv_program))');

        // TODO: Check if movie already in DB

        return $this->render(
            'SLMNWovieMainBundle:html/ajax:searchExternalResult.html.twig',
            array(
                'media' => $result
            )
        );
    }
    public function fetchDescriptionAction(Request $request)
    {
        $id = trim($request->query->get('id'));

        $mediaApi = $this->get('media_api');
        $result = $mediaApi->fetchDescription($id);

        if ($result == false)
        {
            $result = '<div class="row" style="margin-top: 5px;"></div>';
        }
        else
        {
            if (strlen($result) > 300)
            {
                $result = substr($result, 0, 300).'…';
            }
            $result = '<p class="result-plot">'.$result.'</p>';
        }

        return new Response(
            $result,
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }
}