<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    public function findAllForUser($user, $offset=0)
    {
        $followersRepo = $this->getEntityManager()->getRepository('SLMNWovieMainBundle:Follow');
        $followers = $followersRepo->findBy(array('user' => $user));
        $users = array();
        foreach ($followers as $follower)
        {
            $users[] = $follower->getFollow();
        }
        $users[] = $user;

        $query = $this->createQueryBuilder('activity')
            ->where('activity.user IN (:users)')
            ->orWhere('activity.value = :me')
            ->setParameters(array(
                'me' => serialize($user->getId()),
                'users' => $users
            ))
            ->orderBy('activity.time', 'DESC')
            ->setMaxResults(10)
            ->setFirstResult($offset*10)
            ->getQuery();

        $result = $query->getResult();
        return $result;
    }
}