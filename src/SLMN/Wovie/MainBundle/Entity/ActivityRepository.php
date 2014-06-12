<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    public function findAllForUser($user)
    {
        $followersRepo = $this->getEntityManager()->getRepository('SLMNWovieMainBundle:Follow');
        $usersRepo = $this->getEntityManager()->getRepository('SeklMainUserBundle:User');
        $mediasRepo = $this->getEntityManager()->getRepository('SLMNWovieMainBundle:Media');
        $followers = $followersRepo->findBy(array('user' => $user));
        $users = array();
        foreach ($followers as $follower)
        {
            $users[] = $follower->getFollow();
        }
        $users[] = $user;

        $query = $this->createQueryBuilder('activity')
            ->where('activity.user IN (:users)')
            ->setParameter('users', $users)
            ->orderBy('activity.createdAt', 'DESC')
            ->groupBy('activity.user')
            ->addGroupBy('activity.key')
            ->addGroupBy('activity.value')
            ->setMaxResults(25) // TODO: Lazyloading with offset? ( ->setFirstResult( $offset ) )
            ->getQuery();

        $result = $query->getResult();
        $activities = array();
        foreach ($result as $key=>$value)
        {
            $activities[$key] = array(
                'id' => $value->getId(),
                'user' => $value->getUser(),
                'createdAt' => $value->getCreatedAt(),
                'key' => $value->getKey(),
                'value' => $value->getValue()
            );
            switch ($value->getKey())
            {
                case 'follow.added':
                    if (($user=$usersRepo->findOneById($value->getValue()))) // TODO: Check this for all
                    {
                        $activities[$key]['value'] = $user;
                    }
                    else
                    {
                        unset($activities[$key]);
                    }
                    if ($activities[$key]['value'] == $user)
                    {
                        unset($activities[$key]);
                    }
                    break;
                case 'media.added':
                    if (($media=$mediasRepo->findOneById($value->getValue())))
                    {
                        $activities[$key]['value'] = $mediasRepo->findOneById($value->getValue());
                    }
                    else
                    {
                        unset($activities[$key]);
                    }
                    break;
                case 'view.added':
                    $activities[$key]['value']['media'] = $mediasRepo->findOneById($value->getValue()['mediaId']);
                    $activities[$key]['value']['episodeId'] = $value->getValue()['episodeId'];
                    break;
                default:
                    break;
            }
        }

        // TODO: Merge array (like if watches of multiple episodes, not one entry for every episode)

        return $activities;
    }
}