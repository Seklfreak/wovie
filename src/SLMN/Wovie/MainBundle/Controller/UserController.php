<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class UserController extends Controller
{
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContext::AUTHENTICATION_ERROR
            );
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user:login.html.twig',
            array(
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'error'         => $error,
            )
        );
    }

    public function dashboardAction()
    {
        return $this->render(
            'SLMNWovieMainBundle:html/user:dashboard.html.twig',
            array(
            )
        );
    }

    public function shelfAction()
    {
        return $this->render(
            'SLMNWovieMainBundle:html/user:shelf.html.twig',
            array(
            )
        );
    }

    public function addMovieAction(Request $request)
    {
        $newMedia = new Media();

        $fbId = trim($request->query->get('prefill'));
        if ($fbId != '')
        {
            $mediaApi = $this->get('media_api');
            $result = $mediaApi->search('(all (all id:"'.$fbId.'") (any type:/film/film type:/tv/tv_program))');
            if (array_key_exists(0, $result))
            {
                // Preset data
                $result = $result[0];
                $newMedia->setFreebaseId(array_key_exists('mid', $result) ? $result['mid'] : null);
                $newMedia->setTitle(array_key_exists('name', $result) ? $result['name'] : null);
                $newMedia->setDescription(($description=$mediaApi->fetchDescription($fbId)) ? $description : null);
                $newMedia->setReleaseYear(array_key_exists('release_date', $result) ? $result['release_date'] : null);
                $newMedia->setFinalYear(array_key_exists('final_episode', $result) ? $result['final_episode'] : null);
                if (array_key_exists('countries', $result))
                {
                    $countriesString = '';
                    $i = 0;
                    foreach ($result['countries'] as $country)
                    {
                        if ($i > 0)
                        {
                            $countriesString .= ', ';
                        }
                        $countriesString .= $country;
                        $i++;
                    }
                    $newMedia->setCountries($countriesString);
                }
                $newMedia->setRuntime(array_key_exists('runtime', $result) ? $result['runtime'] : null);
                if (array_key_exists('written_by', $result))
                {
                    $writersString = '';
                    $i = 0;
                    foreach ($result['written_by'] as $writer)
                    {
                        if ($i > 0)
                        {
                            $writersString .= ', ';
                        }
                        $writersString .= $writer;
                        $i++;
                    }
                    $newMedia->setWrittenBy($writersString);
                }
                if (array_key_exists('genres', $result))
                {
                    $genresString = '';
                    $i = 0;
                    foreach ($result['genres'] as $genre)
                    {
                        if ($i > 0)
                        {
                            $genresString .= ', ';
                        }
                        $genresString .= $genre;
                        $i++;
                    }
                    $newMedia->setGenres($genresString);
                }
                $newMedia->setNumberOfSeasons(array_key_exists('number_of_seasons', $result) ? $result['number_of_seasons'] : null);
                $newMedia->setNumberOfEpisodes(array_key_exists('number_of_episodes', $result) ? $result['number_of_episodes'] : null);
                if (array_key_exists('poster', $result))
                {
                    // TODO: Download image to disc
                    $newMedia->setPosterImage('https://usercontent.googleapis.com/freebase/v1/image'.$result['poster'].'?maxwidth=400&maxheight=600&mode=fit');
                } // TODO: IMDB image fallback
                $newMedia->setImdbId(array_key_exists('imdbId', $result) ? $result['imdbId'] : null);
                if (array_key_exists('type', $result))
                {
                    switch ($result['type'])
                    {
                        case 'movie':
                            $newMedia->setMediaType(1);
                            break;
                        case 'series':
                            $newMedia->setMediaType(2);
                            break;
                    }
                }
            }
        }

        $newMediaForm = $this->createForm('media', $newMedia);
        $newMediaForm->handleRequest($request);

        if ($fbId == '')
        {
            $newMediaForm->remove('imdbId');
            $newMediaForm->remove('freebaseId');
        }

        if ($newMediaForm->isValid()) {
            $newMedia->setCreatedBy($this->getUser());
            $newMedia->setCreatedAt(new \DateTime());
            $newMedia->setLastUpdatedAt(new \DateTime());
            if ($fbId != '')
            {
                $newMedia->setFreebaseId(array_key_exists('mid', $result) ? $result['mid'] : null);
                if (array_key_exists('poster', $result))
                {
                    // TODO: Download image to disc
                    $newMedia->setPosterImage('https://usercontent.googleapis.com/freebase/v1/image'.$result['poster'].'?maxwidth=400&maxheight=600&mode=fit');
                } // TODO: IMDB image fallback
                $newMedia->setImdbId(array_key_exists('imdbId', $result) ? $result['imdbId'] : null);
            }
            if ($newMedia->getMediaType() == 1) // if movie, reset series fields
            {
                $newMedia->setFinalYear(null);
                $newMedia->setNumberOfEpisodes(null);
                $newMedia->setNumberOfSeasons(null);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($newMedia);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully added the title '.$newMedia->getTitle().'!');
            return $this->redirect($this->generateUrl('slmn_wovie_user_movie_shelf'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user:addMovie.html.twig',
            array(
                'newMediaForm' => $newMediaForm->createView()
            )
        );
    }

    public function searchAction(Request $request)
    {
        $query = trim($request->query->get('q'));

        return $this->render(
            'SLMNWovieMainBundle:html/user:search.html.twig',
            array(
                'query' => $query
            )
        );
    }

} 