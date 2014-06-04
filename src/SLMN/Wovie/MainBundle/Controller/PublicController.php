<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Sekl\Main\UserBundle\Entity\PendingPasswordChange;
use Sekl\Main\UserBundle\Utility\RandomString;

class PublicController extends Controller
{
    public function requestNewPasswordAction(Request $request) {
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

    public function redeemNewPasswordAction(Request $request, $token) {
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
        $myUser = $usersRepo->findOneByUsername($username);

        if (!$myUser)
        {
            throw $this->createNotFoundException('Profile not found!');
        }
        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $myUser, 'key' => 'publicProfile'));
        if (!$publicProfileBool || $publicProfileBool->getValue() == false)
        {
            throw $this->createNotFoundException('Profile not found!');
        }

        return $this->render('SLMNWovieMainBundle:html/public:profile.html.twig', array(
        ));
    }
} 