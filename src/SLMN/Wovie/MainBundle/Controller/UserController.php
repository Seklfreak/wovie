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
                $result = $result[0];
                //var_dump($result);
                $newMedia->setTitle($result['name']);
                $newMedia->setFreebaseId($result['mid']);
                $newMedia->setImdbId($result['imdbId']);
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
            if ($fbId != '') // TODO: Test this
            {
                $newMedia->setFreebaseId($result['mid']);
                $newMedia->setImdbId($result['imdbId']);
            }

            $em = $this->getDoctrine()->getManager();
            //$em->persist($newMedia);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully added the title '.$newMedia->getTitle().'!');
            return $this->redirect($this->generateUrl('slmn_wovie_user_movie_add')); // TODO: Redirect to shelf?
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