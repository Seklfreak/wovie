<?php

namespace SLMN\Wovie\MainBundle;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class SessionFix
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getRequest()->getSession()->save();
    }
} 