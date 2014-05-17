<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ActionController extends Controller
{
    public function searchExternalAction(Request $request)
    {
        $query = trim($request->query->get('q'));

        $mediaApi = $this->get('media_api');
        $result = $mediaApi->search('(all (any name:"'.$query.'" alias:"'.$query.'") (any type:/film/film type:/tv/tv_program))');

        /*
        if ($result != false)
        {
            $result = array_unique($result, SORT_REGULAR);
        }*/
        // TODO: Check if movie already in DB

        return $this->render(
            'SLMNWovieMainBundle:html/ajax:searchExternalResult.html.twig',
            array(
                'media' => $result
            )
        );
    }
}