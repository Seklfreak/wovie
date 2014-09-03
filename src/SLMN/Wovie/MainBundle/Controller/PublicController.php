<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\Entity\PendingUserActivation;
use Sekl\Main\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Sekl\Main\UserBundle\Entity\PendingPasswordChange;
use Sekl\Main\UserBundle\Utility\RandomString;

class PublicController extends Controller
{
    public function requestNewPasswordAction(Request $request)
    {
        $usersRepo = $this->getDoctrine()
            ->getRepository('SeklMainUserBundle:User');

        $newPendingPasswordChange = new PendingPasswordChange();
        $newPendingPasswordChangeForm = $this->createForm('pendingPasswordChange', $newPendingPasswordChange);

        $newPendingPasswordChangeForm->handleRequest($request);

        if ($newPendingPasswordChangeForm->isValid()) {
            $userToChange = $usersRepo->findOneByEmail($newPendingPasswordChangeForm->get('email')->getData());
            if ($userToChange != null) {
                $newPendingPasswordChange->setCreatedAt(new \DateTime());
                $newPendingPasswordChange->setCreatedBy($userToChange);
                $newPendingPasswordChange->setToken(RandomString::randomString(50));

                $em = $this->getDoctrine()->getManager();
                $em->persist($newPendingPasswordChange);
                $em->flush();

                $this->get('templateMailer')->send(
                    $userToChange->getEmail(),
                    'New password',
                    'SLMNWovieMainBundle:email:newPassword.html.twig',
                    array(
                        'newPasswordUrl' => $this->generateUrl('slmn_wovie_public_redeemNewPassword', array(
                                'token' => $newPendingPasswordChange->getToken()
                            ), true)
                    )
                );

                $this->get('session')->getFlashBag()->add('success', 'Please follow the instructions in the email, we sent you.');
                return $this->redirect($this->generateUrl('login'));
            } else {
                $this->get('session')->getFlashBag()->add('error', 'We did not found the user '.$newPendingPasswordChangeForm->get('email')->getData().'!');
                return $this->redirect($this->generateUrl('slmn_wovie_public_requestNewPassword'));
            }
        }

        return $this->render('SLMNWovieMainBundle:html/public:newPassword.html.twig', array(
            'newPendingPasswordChange' => $newPendingPasswordChangeForm->createView()
        ));
    }

    public function redeemNewPasswordAction(Request $request, $token)
    {
        $pendingPasswordChangesRepo = $this->getDoctrine()
            ->getRepository('SeklMainUserBundle:PendingPasswordChange');
        $pendingPasswordChange = $pendingPasswordChangesRepo->findOneByToken($token);
        if ($pendingPasswordChange != null) {
            $pendingUser = $pendingPasswordChange->getCreatedBy();
            if ($pendingPasswordChange->getCreatedAt()->getTimestamp() >= strtotime('-24 hours'))
            {
                if ($pendingUser != null) {
                    $pendingUserForm = $this->createForm('user', $pendingUser)
                        ->remove('username')
                        ->remove('email')
                        ->remove('roles');
                    $pendingUserForm->handleRequest($request);

                    if ($pendingUserForm->isValid()) {
                        $pendingUser->setSalt(md5(uniqid(null, true)));

                        $factory = $this->get('security.encoder_factory');

                        $encoder = $factory->getEncoder($pendingUser);
                        $password = $encoder->encodePassword($pendingUser->getPassword(), $pendingUser->getSalt());
                        $pendingUser->setPassword($password);

                        $em = $this->getDoctrine()->getManager();
                        $em->persist($pendingUser);
                        $em->remove($pendingPasswordChange);
                        $em->flush();

                        $this->get('session')->getFlashBag()->add('success', 'Password changed!');
                        return $this->redirect($this->generateUrl('login'));
                    }

                    return $this->render('SLMNWovieMainBundle:html/public:redeemNewPassword.html.twig', array(
                        'pendingUserForm' => $pendingUserForm->createView()
                    ));
                } else {
                    $this->get('session')->getFlashBag()->add('error', 'User not found!');
                    return $this->redirect($this->generateUrl('login'));
                }
            }
            else
            {
                $em = $this->getDoctrine()->getManager();
                $em->remove($pendingPasswordChange);
                $em->flush();
                $this->get('session')->getFlashBag()->add('error', 'Link expired, please request an new one.');
                return $this->redirect($this->generateUrl('login'));
            }
        } else {
            $this->get('session')->getFlashBag()->add('error', 'URL invalid!');
            return $this->redirect($this->generateUrl('login'));
        }
        return $this->redirect($this->generateUrl('login'));
    }

    public function profileAction($username)
    {
        $usersRepo = $this->getDoctrine()
            ->getRepository('SeklMainUserBundle:User');
        $userOptionsRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:UserOption');
        $mediasRepo = $this->getDoctrine()
            ->getRepository('SLMNWovieMainBundle:Media');
        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not public viewable!');
        }
        $myMedia = $mediasRepo->findBy(array('createdBy' => $myUser), array('title' => 'ASC'));

        return $this->render('SLMNWovieMainBundle:html/public:profile.html.twig', array(
            'user' => $myUser,
            'media' => $myMedia
        ));
    }

    public function detailsMediaAction($id)
    {
        $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media)
        {
            throw $this->createNotFoundException('Media not found!');
        }

        $publicProfileBool = $userOptionsRepo->findOneBy(array(
            'createdBy' => $media->getCreatedBy(),
            'key' => 'publicProfile'
        ));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Media not found!');
        }

        return $this->render(
            'SLMNWovieMainBundle:html/public:details.html.twig',
            array(
                'media' => $media,
                'user' => $media->getCreatedBy()
            )
        );
    }

    public function profileFollowersAction($username)
    {
        $usersRepo = $this->getDoctrine()->getRepository('SeklMainUserBundle:User');
        $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not public viewable!');
        }

        $followsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Follow');
        $followers = $followsRepo->findBy(array('follow' => $myUser), array('createdAt' => 'DESC'));

        return $this->render(
            'SLMNWovieMainBundle:html/public:profileFollowers.html.twig',
            array(
                'user' => $myUser,
                'followers' => $followers
            )
        );
    }

    public function profileFollowingsAction($username)
    {
        $usersRepo = $this->getDoctrine()->getRepository('SeklMainUserBundle:User');
        $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not public viewable!');
        }

        $followsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Follow');
        $followers = $followsRepo->findBy(array('user' => $myUser), array('createdAt' => 'DESC'));

        return $this->render(
            'SLMNWovieMainBundle:html/public:profileFollowings.html.twig',
            array(
                'user' => $myUser,
                'followers' => $followers
            )
        );
    }

    public function indexAction(Request $request)
    {
        // New User
        $newUser = new User();
        $newUserForm = $this->createForm('createUser', $newUser);
        $newUserForm->handleRequest($request);

        if ($newUserForm->isValid()) {
            $factory = $this->get('security.encoder_factory');
            $em = $this->getDoctrine()->getManager();

            $rolesRepo = $this->container->get('doctrine')->getRepository('SeklMainUserBundle:Role');
            $roleUser = $rolesRepo->findOneByRole('ROLE_USER');

            $encoder = $factory->getEncoder($newUser);
            $password = $encoder->encodePassword($newUser->getPassword(), $newUser->getSalt());
            $newUser->setPassword($password);
            $newUser->setIsActive(false);
            $newUser->setRoles(array($roleUser));
            $em->persist($newUser);

            $newPendingUserActivation = new PendingUserActivation();
            $newPendingUserActivation->setUser($newUser);
            $token = RandomString::randomString(50);
            $newPendingUserActivation->setTokenHash(hash('sha256', $token));
            $em->persist($newPendingUserActivation);

            $this->get('templateMailer')->send(
                $newUser->getEmail(),
                'Activate account',
                'SLMNWovieMainBundle:email:accountActivate.html.twig',
                array(
                    'activateUrl' => $this->generateUrl('slmn_wovie_public_activateAccount', array(
                            'username' => $newUser->getUsername(),
                            'token' => $token
                        ), true)
                )
            );

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Thank you! We created your account. Please activate your account with the email we sent to you.');
            return $this->redirect($this->generateUrl('slmn_wovie_public_index'));
        }
        // TODO: If logged in, redirect to dashboard. (If site is public)
        return $this->render('SLMNWovieMainBundle:html/public:index.html.twig', array(
            'newUserForm' => $newUserForm->createView()
        ));
    }

    public function imprintAction(Request $request)
    {
        $newContactForm = $this->createForm('contact');

        $newContactForm->handleRequest($request);

        if ($newContactForm->isValid()) {
            $message = \Swift_Message::newInstance()
                ->setSubject(
                    $this->container->getParameter('slmn_main_userbundle.mail.subject').' contact form | subject: '.$newContactForm->get('subject')->getData()
                )
                ->setFrom(array(
                    $this->container->getParameter('slmn_main_userbundle.mail.sender.email') => $this->container->getParameter('slmn_main_userbundle.mail.sender.title')
                ))
                ->setReplyTo($newContactForm->get('email')->getData())
                ->setTo($this->container->getParameter('slmn_wovie_mainbundle.admin_email'))
                ->setBody(
                    'Message from '.$newContactForm->get('email')->getData().': '."\n".$newContactForm->get('message')->getData()
                )
            ;
            $this->get('mailer')->send($message);

            $this->get('session')->getFlashBag()->add('success', 'Message sent. Thank you!');
            return $this->redirect($this->generateUrl('slmn_wovie_public_imprint'));
        }

        return $this->render('SLMNWovieMainBundle:html/public:imprint.html.twig', array(
            'newContactForm' => $newContactForm->createView()
        ));
    }

    public function activateAccountAction($username, $token)
    {
        $usersRepo = $this->getDoctrine()->getRepository('SeklMainUserBundle:User');
        $pendingUserActivationsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:PendingUserActivation');
        $user = $usersRepo->findOneByUsername($username);
        if (!$user)
        {
            $this->get('session')->getFlashBag()->add('error', 'URL invalid!');
            return $this->redirect($this->generateUrl('slmn_wovie_public_index'));
        }
        $pendingUserActivation = $pendingUserActivationsRepo->findOneBy(array(
            'user' => $user,
            'tokenHash' => hash('sha256', $token)
        ));
        if (!$pendingUserActivation)
        {
            $this->get('session')->getFlashBag()->add('error', 'URL invalid!');
            return $this->redirect($this->generateUrl('slmn_wovie_public_index'));
        }

        $user->setIsActive(true);

        $em = $this->getDoctrine()->getManager();
        $em->remove($pendingUserActivation);
        $em->persist($user);
        $em->flush();
        $this->get('session')->getFlashBag()->add('error', 'Account activated! You can now login.');
        return $this->redirect($this->generateUrl('login'));
    }

    public function profileListsAction($username)
    {
        $usersRepo = $this->getDoctrine()->getRepository('SeklMainUserBundle:User');
        $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not public viewable!');
        }

        return $this->render('SLMNWovieMainBundle:html/public:lists.html.twig', array(
                'user' => $myUser
                )
        );
    }

    public function detailsListAction($username, $listId)
    {
        $usersRepo = $this->getDoctrine()->getRepository('SeklMainUserBundle:User');
        $listsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:MediaList');
        $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');

        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not public viewable!');
        }

        $myList = $listsRepo->findOneBy(array(
            'createdBy' => $myUser,
            'id' => $listId
            )
        );

        if (!$myList)
        {
            throw $this->createNotFoundException('List not found!');
        }

        $this->getDoctrine()->getRepository('SLMNWovieMainBundle:MediaListView')->addView($myList);

        return $this->render('SLMNWovieMainBundle:html/public:list_details.html.twig', array(
                'user' => $myUser,
                'list' => $myList
                )
        );
    }
}
