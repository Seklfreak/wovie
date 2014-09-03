<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;
use SLMN\Wovie\MainBundle\Entity\MediaListView;
use Symfony\Component\HttpFoundation\Session\Session;

class MediaListViewRepository extends EntityRepository
{
    public function addView($medialist)
    {
        $session = new Session();

        $sessionKey = 'view-set-'.$medialist->getId();
        if (!$session->has($sessionKey))
        {
            $newMediaListView = new MediaListView();
            $newMediaListView->setCreatedAt(new \DateTime());
            $newMediaListView->setMedialist($medialist);

            $this->getEntityManager()->persist($newMediaListView);
            $this->getEntityManager()->flush();
            $session->set($sessionKey, new \DateTime());
        }
    }
}
