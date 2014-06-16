<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\Entity\Media;
use SLMN\Wovie\MainBundle\Entity\Profile;
use SLMN\Wovie\MainBundle\Entity\StripeCustomer;
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
        $mediaCopiedFromId = false;
        $fbId = null;

        if (ctype_digit(trim($request->query->get('prefill'))))
        {
            $copyMediaId = intval(trim($request->query->get('prefill')));
            $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
            $copyMedia = $mediasRepo->findOneById($copyMediaId);
            if ($copyMedia)
            {
                if ($copyMedia->getFreebaseId() != null)
                {
                    $fbId = $copyMedia->getFreebaseId();
                }
                $newMedia->setTitle($copyMedia->getTitle());
                $newMedia->setDescription($copyMedia->getDescription());
                $newMedia->setReleaseYear($copyMedia->getReleaseYear());
                $newMedia->setFinalYear($copyMedia->getFinalYear());
                $newMedia->setCountries($copyMedia->getCountries());
                $newMedia->setRuntime($copyMedia->getRuntime());
                $newMedia->setWrittenBy($copyMedia->getWrittenBy());
                $newMedia->setGenres($copyMedia->getGenres());
                $newMedia->setNumberOfEpisodes($copyMedia->getNumberofEpisodes());
                $newMedia->setPosterImage($copyMedia->getPosterImage());
                $newMedia->setImdbId($copyMedia->getImdbId());
                $newMedia->setMediaType($copyMedia->getMediaType());
                $mediaCopiedFromId = true;
            }
        }
        else
        {
            $fbId = trim($request->query->get('prefill'));
        }
        if ($fbId != '')
        {
            $mediaApi = $this->get('media_api');
            $result = $mediaApi->search('(all (all id:"'.$fbId.'") (any type:/film/film type:/tv/tv_program))');
            if (array_key_exists(0, $result))
            {
                // Preset data
                $result = $result[0];
                $newMedia->setFreebaseId(array_key_exists('mid', $result) ? $result['mid'] : $newMedia->getFreebaseId());
                $newMedia->setTitle(array_key_exists('name', $result) ? $result['name'] : $newMedia->getTitle());
                $newMedia->setDescription(($description=$mediaApi->fetchDescription($fbId)) ? $description : $newMedia->getDescription());
                $newMedia->setReleaseYear(array_key_exists('release_date', $result) ? $result['release_date'] : $newMedia->getReleaseYear());
                $newMedia->setFinalYear(array_key_exists('final_episode', $result) ? $result['final_episode'] : $newMedia->getFinalYear());
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
                $newMedia->setRuntime(array_key_exists('runtime', $result) ? $result['runtime'] : $newMedia->getRuntime());
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
                $newMedia->setNumberOfEpisodes(array_key_exists('number_of_episodes', $result) ? $result['number_of_episodes'] : $newMedia->getNumberOfEpisodes());
                $newMedia->setPosterImage(array_key_exists('mid', $result) ? $this->generateUrl('slmn_wovie_image_coverImage', array('freebaseId' => $result['mid']), true) : $newMedia->getPosterImage());
                $newMedia->setImdbId(array_key_exists('imdbId', $result) ? $result['imdbId'] : $newMedia->getImdbId());
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
            if (($episodes=$mediaApi->fetchEpisodes($fbId, true)) && is_array($episodes))
            {
                $newMedia->setNumberOfEpisodes(count($episodes), $newMedia->getEpisodes());
                $newMedia->setEpisodes($episodes);
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
                $newMedia->setPosterImage(array_key_exists('mid', $result) ? $this->generateUrl('slmn_wovie_image_coverImage', array('freebaseId' => $result['mid']), true) : null);
                $newMedia->setImdbId(array_key_exists('imdbId', $result) ? $result['imdbId'] : null);
            }
            if ($mediaCopiedFromId == true)
            {
                $newMedia->setFreebaseId($copyMedia->getFreebaseId());
                $newMedia->setPosterImage($copyMedia->getPosterImage());
                $newMedia->setImdbId($copyMedia->getImdbId());
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
            return $this->redirect($this->generateUrl('slmn_wovie_user_movie_shelf').'#media-'.$newMedia->getId());
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
            if ($userOptions->get('publicProfile', false) == false)
            {
                // Remove all followers
                $query = $this->getDoctrine()->getManager()
                    ->createQueryBuilder()->delete('SLMNWovieMainBundle:Follow', 'follow')
                    ->where('follow.follow = :user')
                    ->setParameter('user', $this->getUser())
                    ->getQuery();
                $query->getResult();
            }

            $this->get('session')->getFlashBag()->add('success', 'Successfully saved your settings.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_general'));
            // TODO: If profile set to public, add an flashbag: you can now view your profile here blablabla
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
        $profilesRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Profile');
        $myUser = $usersRepo->findOneByEmail($this->getUser()->getEmail());
        $myProfile = $profilesRepo->findOneByUser($myUser);
        if (!$myProfile)
        {
            $myProfile = new Profile();
        }

        $accountForm = $this->createForm('editUser', $myUser)
            ->remove('roles');

        $oldPassword = $myUser->getPassword();
        
        $accountForm->handleRequest($request);

        if ($accountForm->isValid()) {
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

            try
            {
                $stripeCustomersRepo = $this->getDoctrine()
                    ->getRepository('SLMNWovieMainBundle:StripeCustomer');
                $stripeCustomer = $stripeCustomersRepo->findOneByUser($this->getUser());
                $customer = \Stripe_Customer::retrieve($stripeCustomer->getCustomerId());
                $customer->email = $myUser->getEmail();
                $customer->save();
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }

            $this->get('session')->getFlashBag()->add('success', 'Successfully changed your account.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_profile'));
        }

        $profileForm = $this->createForm('profile', $myProfile);
        $profileForm->handleRequest($request);
        if ($profileForm->isValid())
        {
            $myProfile->setUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($myProfile);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully updated your profile.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_profile'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-profile.html.twig',
            array(
                'accountForm' => $accountForm->createView(),
                'profileForm' => $profileForm->createView()
            )
        );
    }

    public function settingsBillingAction(Request $request)
    {
        $stripeCustomersRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:StripeCustomer');
        $invoicesRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Invoice');
        $stripeCustomer = $stripeCustomersRepo->findOneByUser($this->getUser());
        $invoices = $invoicesRepo->findBy(array('user' => $this->getUser()), array('date' => 'DESC'));
        $customer = null;
        if ($stripeCustomer)
        {
            try
            {
                $customer = \Stripe_Customer::retrieve($stripeCustomer->getCustomerId());
                $upcomingInvoice = \Stripe_Invoice::upcoming(array('customer' => $stripeCustomer->getCustomerId()));
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }
        }
        if ($customer && ($stripeKey=trim($request->get('stripeToken'))) != null)
        {
            try
            {
                $customer->card = $stripeKey;
                $customer->save();
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }

            $this->get('session')->getFlashBag()->add('success', 'Successfully saved new credit card.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_billing'));
        }
        if ($customer && ($stripeCode=trim($request->get('stripeCode'))) != null)
        {
            try
            {
                $customer->coupon = $stripeCode;
                $customer->save();
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }

            $this->get('session')->getFlashBag()->add('success', 'Successfully added discount.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_billing'));
        }
        
        $stripeCustomerForm = $this->createForm('stripeCustomer', $stripeCustomer);
        $stripeCustomerForm->handleRequest($request);
        if ($stripeCustomerForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($stripeCustomer);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Successfully edited your receipt info.');
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_billing'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-billing.html.twig',
            array(
                'stripeCustomer' => $stripeCustomer,
                'customer' => $customer,
                'invoices' => $invoices,
                'upcomingInvoice' => $upcomingInvoice,
                'stripeCustomerForm' => $stripeCustomerForm->createView()
            )
        );
    }

    public function editMovieAction(Request $request, $id)
    {
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media || $media->getCreatedBy() != $this->getUser())
        {
            throw $this->createNotFoundException('Media not found!');
        }

        $mediaForm = $this->createForm('media', $media);

        $result = array();
        if ($media->getFreebaseId() == null)
        {
            $mediaForm->remove('allowUpdates');
            $mediaForm->remove('imdbId');
            $mediaForm->remove('freebaseId');
        }
        else
        {

            $mediaApi = $this->get('media_api');
            $result = $mediaApi->search('(all (all id:"'.$media->getFreebaseId().'") (any type:/film/film type:/tv/tv_program))');
            if (is_array($result) && array_key_exists(0, $result))
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

    public function detailsMediaAction($id)
    {
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media || $media->getCreatedBy() != $this->getUser())
        {
            throw $this->createNotFoundException('Media not found!');
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user:detailsMedia.html.twig',
            array(
                'media' => $media
            )
        );
    }

    public function activityAction()
    {
        return $this->render(
            'SLMNWovieMainBundle:html/user:activity.html.twig'
        );
    }
}