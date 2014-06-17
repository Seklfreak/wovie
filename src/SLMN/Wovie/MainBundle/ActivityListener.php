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

    public function __construct($container, $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    protected function getUser()
    {
        return $this->container->get('security.context')->getToken()->getUser();
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();

        if ($entity instanceof Media)
        {
            $activity = new Activity();
            $activity->setUser($this->getUser());
            $activity->setCreatedAt(new \DateTime());
            $activity->setKey('media.added');
            $activity->setValue($entity->getId());
            $em->persist($activity);
            $this->logger->info('Created activity '.$activity->getKey().' for '.serialize($activity->getValue()));
        }
        else if ($entity instanceof View)
        {
            $activity = new Activity();
            $activity->setUser($this->getUser());
            $activity->setCreatedAt(new \DateTime());
            $activity->setKey('view.added');
            $activity->setValue(
                array(
                    'mediaId' => $entity->getMedia()->getId(),
                    'episodeId' => $entity->getEpisode()
                )
            );
            $em->persist($activity);
            $this->logger->info('Created activity '.$activity->getKey().' for '.serialize($activity->getValue()));
        }
        else if ($entity instanceof Follow)
        {
            $activity = new Activity();
            $activity->setUser($this->getUser());
            $activity->setCreatedAt(new \DateTime());
            $activity->setKey('follow.added');
            $activity->setValue($entity->getFollow()->getId());
            $em->persist($activity);
            $this->logger->info('Created activity '.$activity->getKey().' for '.serialize($activity->getValue()));
        }
    }

    public function preRemove(LifecycleEventArgs $args)
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