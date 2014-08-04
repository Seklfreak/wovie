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
        $referrerU = $this->get('wovie.utility.referer_service');
        $referrerU->setForm('addMedia');

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
                if (!$newMedia->getPosterImage())
                {
                    $newMedia->setPosterImage(array_key_exists('mid', $result) ? $this->generateUrl('slmn_wovie_image_coverImage', array('freebaseId' => $result['mid']), true) : $newMedia->getPosterImage());
                }
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
                $newMedia->setImdbId($copyMedia->getImdbId());
                if ($copyMedia->getCustomCoverKey())
                {
                    $newMedia->setPosterImage(null);
                }
            }
            if ($newMedia->getMediaType() == 1) // if movie, reset series fields
            {
                $newMedia->setFinalYear(null);
                $newMedia->setNumberOfEpisodes(null);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($newMedia);
            $em->flush();

            if ($mediaCopiedFromId == true && $copyMedia->getCustomCoverKey())
            {
                $customCoversHandle = $this->get('wovie.customCovers');
                $customCoversHandle->copy($copyMedia, $newMedia);
            }

            $this->get('session')->getFlashBag()->add('success', 'Successfully added the title '.$newMedia->getTitle().'!');
            return $this->redirect($referrerU->getReferrer('slmn_wovie_user_movie_shelf', array('/search')).'#media-'.$newMedia->getId());
        }
        else
        {
            $referrerU->setReferrer();
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

        $finder = $this->container->get('fos_elastica.finder.wovie.media');
        $boolQuery = new \Elastica\Query\Bool();

        $createdByQuery = new \Elastica\Query\Nested();
        $createdByQuery->setQuery(new \Elastica\Query\Term(array('id' => array('value' => $this->getUser()->getId()))));
        $createdByQuery->setPath('createdBy');
        $boolQuery->addMust($createdByQuery);

        $shouldQueries = new \Elastica\Query\Bool();

        //$fieldQuery = new \Elastica\Query\Fuzzy();
        $fieldQuery = new \Elastica\Query\Match();
        $fieldQuery->setField('title', $queryString);
        $shouldQueries->addShould($fieldQuery);
        $fieldQuery = new \Elastica\Query\Match();
        $fieldQuery->setFieldQuery('description', $queryString);
        $shouldQueries->addShould($fieldQuery);

        $boolQuery->addMust($shouldQueries);

        $result = $finder->find($boolQuery, 15);

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
            return $this->redirect($this->generateUrl('slmn_wovie_user_feedback'));
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
                switch ($key)
                {
                    case 'language':
                        $request->getSession()->set('_locale', $value);
                        $request->setLocale($value);
                        break;
                    default:
                        break;
                }
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
        $stripeCustomersRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:StripeCustomer');
        $myUser = $usersRepo->findOneByEmail($this->getUser()->getEmail());
        $myProfile = $profilesRepo->findOneByUser($myUser);
        if (!$myProfile)
        {
            $myProfile = new Profile();
        }
        $stripeCustomer = $stripeCustomersRepo->findOneByUser($this->getUser());
        if ($stripeCustomer)
        {
            try
            {
                $customer = \Stripe_Customer::retrieve($stripeCustomer->getCustomerId());
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }
        }
        if (!$stripeCustomer || !$customer)
        {
            throw $this->createAccessDeniedException('User not found!');
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
        
        $reactivateAccountForm = $this->createFormBuilder(array())
            ->add('reactivateAccount', 'submit')
            ->getForm();

        $reactivateAccountForm->handleRequest($request);

        if ($reactivateAccountForm->isValid()) {
            try
            {
                $subscription = $customer->subscriptions->retrieve($customer->subscriptions->data[0]->id);
                $subscription->plan = $subscription->plan;
                $subscription->save();
                $this->get('session')->getFlashBag()->add('success', 'Successfully reactivated your account.');
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
                $this->get('session')->getFlashBag()->add('error', 'Internal error!');
            }
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_profile'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-profile.html.twig',
            array(
                'accountForm' => $accountForm->createView(),
                'profileForm' => $profileForm->createView(),
                'reactivateAccountForm' => $reactivateAccountForm->createView(),
                'stripeCustomer' => $stripeCustomer
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
        $upcomingInvoice = null;
        if ($stripeCustomer)
        {
            try
            {
                $customer = \Stripe_Customer::retrieve($stripeCustomer->getCustomerId());
                $upcomingInvoice = \Stripe_Invoice::upcoming(array('customer' => $stripeCustomer->getCustomerId()));
            }
            catch (\Stripe_Error $e)
            {
                $logger = $this->get('logger');
                if (strpos($e->getMessage(), 'No upcoming invoices for customer') === false)
                {
                    $logger->error('Stripe: '.$e->getMessage());
                }
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
                $this->get('session')->getFlashBag()->add('success', 'Successfully saved new credit card.');
            }
            catch (\Stripe_CardError $e)
            {
                $body = $e->getJsonBody();
                $this->get('session')->getFlashBag()->add('error', 'Your card were declined.');
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_billing'));
        }
        if ($customer && $invoices && $request->get('payNowLastInvoice'))
        {
            try
            {
                $lastInvoices = \Stripe_Invoice::all(array(
                        "customer" => $stripeCustomer->getCustomerId(),
                        "limit" => 1
                    )
                );
                $lastInvoices->data[0]->pay();
                $this->get('session')->getFlashBag()->add('success', 'Invoice paid.');
            }
            catch(\Stripe_CardError $e)
            {
                $this->get('session')->getFlashBag()->add('error', 'Your card were declined.');
            }
            catch(\Stripe_Error $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
                $this->get('session')->getFlashBag()->add('error', 'Internal error! An administrator has been notified.');
            }
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

    public function settingsAccountCancelAction(Request $request)
    {
        $stripeCustomersRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:StripeCustomer');
        $stripeCustomer = $stripeCustomersRepo->findOneByUser($this->getUser());
        $customer = null;
        if ($stripeCustomer)
        {
            try
            {
                $customer = \Stripe_Customer::retrieve($stripeCustomer->getCustomerId());
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
            }
        }
        if (!$stripeCustomer || !$customer)
        {
            throw $this->createAccessDeniedException('User not found!');
        }

        $cancelAccountForm = $this->createFormBuilder(array())
            ->add('cancelAccount', 'submit')
            ->getForm();

        $cancelAccountForm->handleRequest($request);

        if ($cancelAccountForm->isValid()) {
            try
            {
                $customer->subscriptions->data[0]->cancel(array('at_period_end' => true));
                $this->get('session')->getFlashBag()->add('success', 'Successfully cancelled your account.');
            }
            catch (\Exception $e)
            {
                $logger = $this->get('logger');
                $logger->error('Stripe: '.$e->getMessage());
                $this->get('session')->getFlashBag()->add('error', 'Internal error!');
            }
            return $this->redirect($this->generateUrl('slmn_wovie_user_settings_profile'));
        }

        return $this->render(
            'SLMNWovieMainBundle:html/user/settings:tab-profile-cancelaccount.html.twig',
            array(
                'cancelAccountForm' => $cancelAccountForm->createView()
            )
        );
    }

    public function editMovieAction(Request $request, $id)
    {
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $referrerU = $this->get('wovie.utility.referer_service');
        $referrerU->setForm('editMedia');
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
            return $this->redirect($referrerU->getReferrer('slmn_wovie_user_movie_shelf'));
        }
        else
        {
            $referrerU->setReferrer();
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

    public function activityAction($id)
    {
        if ($id > 0)
        {
            $activitiesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Activity');
            $activity = $activitiesRepo->findOneById($id);
            return $this->render('SLMNWovieMainBundle:html/user:activitySingle.html.twig', array(
                'activity' => $activity
            ));
        }

        return $this->render('SLMNWovieMainBundle:html/user:activity.html.twig');
    }
}