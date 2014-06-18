<?php

namespace SLMN\Wovie\MainBundle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use SLMN\Wovie\MainBundle\Entity\Activity;
use SLMN\Wovie\MainBundle\Entity\Follow;
use SLMN\Wovie\MainBundle\Entity\Media;
use SLMN\Wovie\MainBundle\Entity\View;

class ActivityListener
{
    /*
     * Activities:
     * - media.added -> when an media was added to an users library
     * - view.added -> when an view from an user was added to the database
     * - follow.added -> when an follow from an user was added to the database
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
    }

    public function preRemove(LifecycleEventArgs $args) // TODO: Work via queue
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();
        $activitiesRepo = $em->getRepository('SLMNWovieMainBundle:Activity');

        if ($entity instanceof Media)
        {
            $activities = $activitiesRepo->findBy(array(
                    'user' => $this->getUser(),
                    'key' => 'media.added',
                    'value' => $entity->getId()
                ),
                array(
                    'createdAt' => 'DESC'
                )
            );
            if (array_key_exists(0, $activities) && $activities[0] != null)
            {
                $em->remove($activities[0]);
                $this->logger->info('Deleted activity '.$activities[0]->getKey().': #'.$activities[0]->getId());
            }
        }
        else if ($entity instanceof View)
        {
            $query = $activitiesRepo->createQueryBuilder('activity')
                ->where('activity.user = :user')
                ->andWhere('activity.key = :key')
                ->andWhere('activity.value = :value')
                ->setParameters(array(
                    'user' => $this->getUser(),
                    'key' => 'view.added',
                    'value' => serialize(array(
                            'mediaId' => $entity->getMedia()->getId(),
                            'episodeId' => $entity->getEpisode()
                        ))
                    ))
                ->orderBy('activity.createdAt', 'DESC')
                ->getQuery();
            $activities = $query->getResult();

            if (array_key_exists(0, $activities) && $activities[0] != null)
            {
                $em->remove($activities[0]);
                $this->logger->info('Deleted activity '.$activities[0]->getKey().': #'.$activities[0]->getId());
            }
        }
        else if ($entity instanceof Follow)
        {
            $activities = $activitiesRepo->findBy(array(
                    'user' => $this->getUser(),
                    'key' => 'follow.added',
                    'value' => $entity->getFollow()->getId()
                ),
                array(
                    'createdAt' => 'DESC'
                )
            );
            if (array_key_exists(0, $activities) && $activities[0] != null)
            {
                $em->remove($activities[0]);
                $this->logger->info('Deleted activity '.$activities[0]->getKey().': #'.$activities[0]->getId());
            }
        }
    }
}