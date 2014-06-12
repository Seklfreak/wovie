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
            ->andWhere('activity.createdAt < :dateStart')
            ->andWhere('activity.createdAt > :dateTo')
            ->setParameters(array(
                'users' => $users,
                'dateStart' => $dateStart,
                'dateTo' => $dateTo
            ))
            ->orderBy('activity.createdAt', 'DESC')
            ->groupBy('activity.user')
            ->addGroupBy('activity.key')
            ->addGroupBy('activity.value')
            ->getQuery();
        $followingYouQuery = $this->createQueryBuilder('activity')
            ->where('activity.value = :me')
            ->andWhere('activity.user NOT IN (:users)')
            ->andWhere('activity.key = :key')
            ->andWhere('activity.createdAt < :dateStart')
            ->andWhere('activity.createdAt > :dateTo')
            ->setParameters(array(
                'me' => serialize($user->getId()),
                'key' => 'follow.added',
                'users' => $users,
                'dateStart' => $dateStart,
                'dateTo' => $dateTo
            ))
            ->orderBy('activity.createdAt', 'DESC')
            ->groupBy('activity.user')
            ->addGroupBy('activity.key')
            ->addGroupBy('activity.value')
            ->getQuery();

        $result = $query->getResult();
        $followingYou = $followingYouQuery->getResult();
        $result = new ArrayCollection(array_merge($result, $followingYou));
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
                    if (($oUser=$usersRepo->findOneById($value->getValue())))
                    {
                        $activities[$key]['value'] = $oUser;
                    }
                    else
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

        return $activities;
    }
}