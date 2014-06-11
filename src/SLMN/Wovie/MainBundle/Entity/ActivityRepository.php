<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    public function findAllForUser($user)
    {
        $followersRepo = $this->getEntityManager()->getRepository('SLMNWovieMainBundle:Follow');
        $usersRepo = $this->getEntityManager()->getRepository('SeklMainUserBundle:User');
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
                    $activities[$key]['value'] = $usersRepo->findOneById($value->getValue());

                    if ($activities[$key]['value'] == $user)
                    {
                        unset($activities[$key]);
                    }
                    break;
                default:
                    break;
            }
        }

        // TODO: Remove duplicates

        return $activities;
    }
}