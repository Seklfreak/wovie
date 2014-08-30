<?php

namespace SLMN\Wovie\MainBundle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use SLMN\Wovie\MainBundle\Entity\Follow;
use SLMN\Wovie\MainBundle\Entity\Media;
use SLMN\Wovie\MainBundle\Entity\View;
use SLMN\Wovie\MainBundle\Entity\MediaList;

class ActivityListener
{
    /*
     * Activities:
     * - media.added -> when an media was added to an users library
     * - view.added -> when an view from an user was added to the database
     * - follow.added -> when an follow from an user was added to the database
     * - favorite.added -> when an media was favorited
     * - medialist.added -> when an medialist has been created
     */

    protected $container;
    protected $logger;
    protected $rabbitCreateActivity;

    public function __construct($container, $logger, $rabbitCreateActivity)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->rabbitCreateActivity = $rabbitCreateActivity;
    }

    protected function getUser()
    {
        return $this->container->get('security.context')->getToken()->getUser();
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Media)
        {
            if ($eventArgs->hasChangedField('favorite') && $eventArgs->getNewValue('favorite') == true)
            {
                $this->rabbitCreateActivity->publish(serialize(array(
                    'key' => 'favorite.added',
                    'userId' => $this->getUser()->getId(),
                    'createdAt' => new \DateTime(),
                    'value' => array(
                        'mediaId' => $entity->getId()
                    )
                )));
                $this->logger->info('Published activity "favorite.added" for user #'.$this->getUser()->getId());
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();

        if ($entity instanceof Media)
        {
            $this->rabbitCreateActivity->publish(serialize(array(
                'key' => 'media.added',
                'userId' => $this->getUser()->getId(),
                'createdAt' => new \DateTime(),
                'value' => array(
                    'mediaId' => $entity->getId()
                )
            )));
            $this->logger->info('Published activity "media.added" for user #'.$this->getUser()->getId());
        }
        else if ($entity instanceof View)
        {
            $this->rabbitCreateActivity->publish(serialize(array(
                'key' => 'view.added',
                'userId' => $this->getUser()->getId(),
                'createdAt' => new \DateTime(),
                'value' => array(
                    'mediaId' => $entity->getMedia()->getId(),
                    'episodeId' => $entity->getEpisode()
                )
            )));
            $this->logger->info('Published activity "view.added" for user #'.$this->getUser()->getId());
        }
        else if ($entity instanceof Follow)
        {
            $this->rabbitCreateActivity->publish(serialize(array(
                'key' => 'follow.added',
                'userId' => $this->getUser()->getId(),
                'createdAt' => new \DateTime(),
                'value' => array(
                    'userId' => $entity->getFollow()->getId()
                )
            )));
            $this->logger->info('Published activity "follow.added" for user #'.$this->getUser()->getId());
        }
        else if ($entity instanceof MediaList)
        {
            $this->rabbitCreateActivity->publish(serialize(array(
                'key' => 'medialist.added',
                'userId' => $this->getUser()->getId(),
                'createdAt' => new \DateTime(),
                'value' => array(
                    'medialistId' => $entity->getId()
                )
            )));
            $this->logger->info('Published activity "medialist.added" for user #'.$this->getUser()->getId());
        }
    }
}
