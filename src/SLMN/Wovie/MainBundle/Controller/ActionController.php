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
        $result = $mediaApi->search(
            '(all (any name:"'.$query.'" alias:"'.$query.'") (any type:/film/film type:/tv/tv_program))',
            array(
                'name',
                //'type',
                '/common/topic/description',
                '/tv/tv_program/air_date_of_first_episode',
                '/tv/tv_program/air_date_of_final_episode',
                '/tv/tv_program/country_of_origin',
                '/tv/tv_program/episode_running_time',
                '/tv/tv_program/program_creator',
                '/tv/tv_program/genre',
                '/tv/tv_program/number_of_seasons',
                '/tv/tv_program/number_of_episodes',
                '/film/film/initial_release_date',
                //'/film/film/country',
                '/film/film_cut/runtime',
                '/film/film/written_by',
                '/film/film/genre',
                '/common/topic/image',
                '/imdb/topic/title_id'
            )
        );

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