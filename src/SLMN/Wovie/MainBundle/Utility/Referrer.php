<?php

namespace SLMN\Wovie\MainBundle\Utility;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\SecurityContext;

class Referrer
{
    protected $router;
    protected $request;
    protected $form;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function setRequest(RequestStack $request_stack)
    {
        $this->request = $request_stack->getCurrentRequest();
    }

    protected function getSession()
    {
        return $this->request->getSession();
    }

    protected function strposarray($haystack, $needle, $offset=0) {
        if (!is_array($needle)) $needle = array($needle);
        foreach($needle as $query)
        {
            if(strpos($haystack, $query, $offset) !== false) return true;
        }
        return false;
    }

    public function setForm($name)
    {
        $this->form = $name;
    }

    public function setReferrer()
    {
        $this->getSession()->set('betterReferrer-'.$this->form, $this->request->headers->get('referer'));
        return true;
    }

    public function getReferrer($fallbackRoute, $blacklist=array())
    {
        $referrer = $this->getSession()->get('betterReferrer-'.$this->form);
        $lastPath = substr($referrer, strpos($referrer, $this->request->getBaseUrl()));

        if (empty($lastPath) || $this->strposarray($lastPath, $blacklist))
        {
            return $this->router->generate($fallbackRoute);
        }

        return $lastPath;
    }
}
