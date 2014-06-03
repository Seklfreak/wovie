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
                $newMedia->setNumberOfEpisodes(array_key_exists('number_of_episodes', $result) ? $result['number_of_episodes'] : null);
                $newMedia->setPosterImage(array_key_exists('mid', $result) ? $this->generateUrl('slmn_wovie_image_coverImage', array('freebaseId' => $result['mid'])) : null);
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

        if ($fbId == '')
        {
            $newMediaForm->remove('imdbId');
            $newMediaForm->remove('freebaseId');
        }

        $newMediaForm->handleRequest($request);

        if ($newMediaForm->isValid()) {
            $newMedia->setCreatedBy($this->getUser());
            $newMedia->setCreatedAt(new \DateTime());
            $newMedia->setLastUpdatedAt(new \DateTime());
            if ($fbId != '')
            {
                $newMedia->setFreebaseId(array_key_exists('mid', $result) ? $result['mid'] : null);
                $newMedia->setPosterImage(array_key_exists('mid', $result) ? $this->generateUrl('slmn_wovie_image_coverImage', array('freebaseId' => $result['mid'])) : null);
                $newMedia->setImdbId(array_key_exists('imdbId', $result) ? $result['imdbId'] : null);
            }
            if ($newMedia->getMediaType() == 1) // if movie, reset series fields
            {
                $newMedia->setFinalYear(null);
                $newMedia->setNumberOfEpisodes(null);
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
        $queryString = trim($request->query->get('q'));

        $moviesRepo = $this->getDoctrine()->getManager()->getRepository('SLMNWovieMainBundle:Media');
        $query = $moviesRepo->createQueryBuilder('media')
            ->where('media.title LIKE :query')
            ->andWhere('media.createdBy = :user')
            ->setParameters(array(
                'query' => '%'.$queryString.'%',
                'user' => $this->getUser()
            ))
            ->getQuery();
        $result = $query->getResult();

        return $this->render(
            'SLMNWovieMainBundle:html/user:search.html.twig',
            array(
                'result' => $result,
                'query' => $queryString
            )
        );
    }

    public function feedbackAction(Request $request)
    {
        $newFeedbackForm = $this->createForm('feedback');
        $newFeedbackForm->remove('email');

        $newFeedbackForm->handleRequest($request);

        if ($newFeedbackForm->isValid()) {
            $this->get('templateMailer')->send(
                $this->container->getParameter('slmn_wovie_mainbundle.admin_email'),
                'Feedback: '.$newFeedbackForm->get('subject')->getData(),
                'SLMNWovieMainBundle:email:feedback.html.twig',
                array(
                    'username' => $this->getUser()->getUsername(),
                    'myEmail' => $this->getUser()->getEmail(),
                    'message' => $newFeedbackForm->get('message')->getData()
                )
            );

            $this->get('session')->getFlashBag()->add('success', 'Feedback sent. Thank you!');
            return $this->redirect($this->generateUrl('slmn_wovie_user_dashboard'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user:feedback.html.twig',
            array(
                'newFeedbackForm' => $newFeedbackForm->createView()
            )
        );
    }

    public function settingsGeneralAction(Request $request)
    {
        $generalSettingsForm = $this->createForm('generalSettings');
        $userOptions = $this->get('userOption');

        $generalSettingsForm->handleRequest($request);

        if ($generalSettingsForm->isValid()) {
            foreach($generalSettingsForm->getData() as $key=>$value)
            {
                $userOptions->set($key, $value);
            }
            $this->get('session')->getFlashBag()->add('success', 'Successfully saved your settings.');
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-general.html.twig',
            array(
                'generalSettingsForm' => $generalSettingsForm->createView()
            )
        );
    }

    public function settingsProfileAction(Request $request)
    {
        $usersRepo = $this->getDoctrine()
            ->getRepository('SeklMainUserBundle:User');
        $myUser = $usersRepo->findOneByEmail($this->getUser()->getEmail());

        $profileForm = $this->createForm('editUser', $myUser)
            ->remove('username')
            ->remove('roles');

        $oldPassword = $myUser->getPassword();
        
        $profileForm->handleRequest($request);

        if ($profileForm->isValid()) {
            if ($myUser->getPassword() != '')
            {
                $myUser->setSalt(md5(uniqid(null, true)));

                $factory = $this->get('security.encoder_factory');

                $encoder = $factory->getEncoder($myUser);
                $password = $encoder->encodePassword($myUser->getPassword(), $myUser->getSalt());
                $myUser->setPassword($password);
            }
            else
            {
                $myUser->setPassword($oldPassword);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($myUser);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully changed your profile.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_profile'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-profile.html.twig',
            array(
                'profileForm' => $profileForm->createView()
            )
        );
    }

    public function editMovieAction(Request $request, $id)
    {
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media)
        {
            throw $this->createNotFoundException('Item not found!');
        }

        $mediaForm = $this->createForm('media', $media);

        $result = array();
        if ($media->getFreebaseId() == null)
        {
            $mediaForm->remove('imdbId');
            $mediaForm->remove('freebaseId');
        }
        else
        {

            $mediaApi = $this->get('media_api');
            $result = $mediaApi->search('(all (all id:"'.$media->getFreebaseId().'") (any type:/film/film type:/tv/tv_program))');
            if (array_key_exists(0, $result))
            {
                $result = $result[0];
                $result['description'] = $mediaApi->fetchDescription($media->getFreebaseId());
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
                    $result['countries'] = $countriesString;
                }
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
                    $result['written_by'] = $writersString;
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
                    $result['genres'] = $genresString;
                }
            }
        }

        $oldFreebaseId = $media->getFreebaseId();
        $oldPosterImage = $media->getPosterImage();
        $oldImdbId = $media->getImdbId();

        $mediaForm->handleRequest($request);

        if ($mediaForm->isValid()) {
            $media->setLastUpdatedAt(new \DateTime());

            // Reset disabled fields
            $media->setFreebaseId($oldFreebaseId);
            $media->setPosterImage($oldPosterImage);
            $media->setImdbId($oldImdbId);
            if ($media->getMediaType() == 1) // if movie, reset series fields
            {
                $media->setFinalYear(null);
                $media->setNumberOfEpisodes(null);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($media);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully edited the title '.$media->getTitle().'!');
            return $this->redirect($this->generateUrl('slmn_wovie_user_movie_shelf'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user:editMovie.html.twig',
            array(
                'mediaForm' => $mediaForm->createView(),
                'freebaseData' => $result,
                'mediaId' => $media->getId()
            )
        );
    }
}