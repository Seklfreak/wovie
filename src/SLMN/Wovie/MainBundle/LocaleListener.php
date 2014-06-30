<?php

namespace SLMN\Wovie\MainBundle;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LocaleListener implements EventSubscriberInterface
{
    private $userOption;

    private $defaultLang = 'en';

    public function __construct(
        $userOption
    )
    {
        $this->userOption = $userOption;
    }

    public function setLocaleForAuthenticatedUser(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        $lang = $this->userOption->get('language', $this->defaultLang);

        $session->set('_locale', $lang);
        $request->setLocale($lang);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$request->hasPreviousSession())
        {
            return;
        }

        if (($locale = $session->get('_locale')))
        {
            $request->setLocale($locale);
        }
        else
        {
            $request->setLocale($request->getPreferredLanguage());
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}