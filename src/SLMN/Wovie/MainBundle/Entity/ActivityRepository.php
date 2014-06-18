<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    public function findAllForUser($user, $offset=0)
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

        $dateStart = new \DateTime();
        $dateStart->modify('-'.(24*intval($offset)).' hour');
        $dateTo = new \DateTime();
        $dateTo->modify('-'.(24*(intval($offset)+1)).' hour');
        $query = $this->createQueryBuilder('activity')
            ->where('activity.user IN (:users)')
            ->andWhere('activity.time < :dateStart')
            ->andWhere('activity.time > :dateTo')
            ->orWhere('activity.value = :me')
            ->andWhere('activity.time < :dateStart')
            ->andWhere('activity.time > :dateTo')
            ->setParameters(array(
                'me' => serialize($user->getId()),
                'users' => $users,
                'dateStart' => $dateStart,
                'dateTo' => $dateTo
            ))
            ->orderBy('activity.time', 'DESC')
            ->getQuery();

        $result = $query->getResult();
        return $result;

        return $activities;
    }
}