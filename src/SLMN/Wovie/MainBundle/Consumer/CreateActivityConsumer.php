<?php

namespace SLMN\Wovie\MainBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use SLMN\Wovie\MainBundle\Entity\Activity;

class CreateActivityConsumer implements ConsumerInterface
{
    protected $timeRange = '10 minute';

    protected $em;
    protected $mediaApi;

    protected $activitiesRepo;
    protected $usersRepo;
    protected $mediasRepo;

    public function __construct($em, $mediaApi)
    {
        $this->em = $em;
        $this->mediaApi = $mediaApi;
        $this->activitiesRepo = $this->em->getRepository('SLMNWovieMainBundle:Activity');
        $this->usersRepo = $this->em->getRepository('SeklMainUserBundle:User');
        $this->mediasRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
    }

    protected function getInTimerange($time, $key, $user)
    {
        $timeStart = clone $time;
        $timeStart->modify('-'.$this->timeRange);
        $timeEnd = clone $time;
        $timeEnd->modify('+'.$this->timeRange);
        $query = $this->activitiesRepo->createQueryBuilder('activity')
            ->where('activity.user = :user')
            ->andWhere('activity.key = :key')
            ->andWhere('activity.time > :timeStart')
            ->andWhere('activity.time < :timeEnd')
            ->setParameters(array(
                'user' => $user,
                'key' => $key,
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd
            ))
            ->orderBy('activity.time', 'DESC')
            ->getQuery();
        $result = $query->getResult();
        if (count($result) > 0)
        {
            return $result[0];
        }
        else
        {
            return null;
        }
    }

    public function execute(AMQPMessage $msg)
    {
        echo '---'."\n"; // TODO: remove
        $value = unserialize($msg->body);
        switch ($value['key'])
        {
            case 'view.added':
                echo 'Received activity: '.$value['key']."\n"; // TODO: Add time
                $user = $this->usersRepo->findOneById($value['userId']);
                $media = $this->mediasRepo->findOneById($value['value']['mediaId']);
                if (!$user)
                {
                    echo ' => user #'.$value['userId'].' not found. => rejected'."\n";
                    return false;
                }
                if (!$media)
                {
                    echo ' => media #'.$value['value']['mediaId'].' not found. => rejected'."\n";
                    return false;
                }

                $activity = $this->getInTimerange($value['createdAt'], $value['key'], $user);
                if ($activity == null)
                {
                    $activity = new Activity();
                    $activity->setUser($user);
                    $activity->setTime($value['createdAt']);
                    $activity->setKey('view.added');

                    if ($value['value']['episodeId'] == null)
                    {
                        $activityValue = array(
                            'mediaId' => $value['value']['mediaId']
                        );
                    }
                    else
                    {
                        $episodes = array();
                        if ($media->getFreebaseId())
                        {
                            $episodes = $this->mediaApi->fetchEpisodes($media->getFreebaseId(), true);
                        }
                        if (
                            array_key_exists($value['value']['episodeId'], $episodes) &&
                            array_key_exists('season', $episodes[$value['value']['episodeId']]) &&
                            array_key_exists('episode', $episodes[$value['value']['episodeId']])
                        )
                        {
                            $thisEpisode = $episodes[$value['value']['episodeId']];
                            $activityValue = array(
                                'mediaId' => $value['value']['mediaId'],
                                'seasons' => array(
                                    $thisEpisode['season'] => array(
                                        $value['value']['episodeId'] => $thisEpisode['episode']
                                    )
                                )
                            );
                        }
                        else
                        {
                            $activityValue = array(
                                'mediaId' => $value['value']['mediaId'],
                                'seasons' => array(
                                    1 => array(
                                        $value['value']['episodeId'] => $value['value']['episodeId']
                                    )
                                )
                            );
                        }
                    }
                    $activity->setValue(array($activityValue));

                    $this->em->persist($activity);
                    $this->em->flush();
                    echo ' => created new activity: #'.$activity->getId()."\n";
                    return true;
                }
                else
                {
                    $mediaInArray = false;
                    $activityValue = $activity->getValue();
                    foreach ($activityValue as $aKey=>$aMedia)
                    {
                        if ($aMedia['mediaId'] == $value['value']['mediaId'])
                        {
                            $mediaInArray = true;
                            if ($value['value']['episodeId'] == null)
                            {
                                echo ' => already in activity: #'.$activity->getId()."\n";
                                return true;
                            }
                            else
                            {
                                $episodes = array();
                                if ($media->getFreebaseId())
                                {
                                    $episodes = $this->mediaApi->fetchEpisodes($media->getFreebaseId(), true);
                                }
                                $season = 1;
                                $episodeInSeason = $value['value']['episodeId'];

                                if (
                                    array_key_exists($value['value']['episodeId'], $episodes) &&
                                    array_key_exists('season', $episodes[$value['value']['episodeId']]) &&
                                    array_key_exists('episode', $episodes[$value['value']['episodeId']])
                                )
                                {
                                    $thisEpisode = $episodes[$value['value']['episodeId']];
                                    $season = $thisEpisode['season'];
                                    $episodeInSeason = $thisEpisode['episode'];
                                }
                                if (array_key_exists($season, $aMedia['seasons']))
                                {
                                    if (array_key_exists($value['value']['episodeId'], $aMedia['seasons'][$season]))
                                    {
                                        echo ' => episode in media already activity: #'.$activity->getId()."\n";
                                        return true;
                                    }
                                    else
                                    {
                                        $activityValue[$aKey]['seasons'][$season][$value['value']['episodeId']] = $episodeInSeason;
                                        $activity->setValue($activityValue);
                                        $this->em->persist($activity);
                                        $this->em->flush();
                                        echo ' => episode added to media in activity: #'.$activity->getId()."\n";
                                        return true;
                                    }
                                }
                                else
                                {
                                    $activityValue[$aKey]['seasons'][$season] = array(
                                        $value['value']['episodeId'] => $episodeInSeason
                                    );
                                    $activity->setValue($activityValue);
                                    $this->em->persist($activity);
                                    $this->em->flush();
                                    echo ' => season added to media in activity: #'.$activity->getId()."\n";
                                    return true;
                                }
                            }
                        }
                    }
                    if ($mediaInArray == false)
                    {
                        if ($value['value']['episodeId'] == null)
                        {
                            $activityValue[] = array(
                                'mediaId' => $value['value']['mediaId']
                            );
                        }
                        else
                        {
                            $episodes = array();
                            if ($media->getFreebaseId())
                            {
                                $episodes = $this->mediaApi->fetchEpisodes($media->getFreebaseId(), true);
                            }
                            if (
                                array_key_exists($value['value']['episodeId'], $episodes) &&
                                array_key_exists('season', $episodes[$value['value']['episodeId']]) &&
                                array_key_exists('episode', $episodes[$value['value']['episodeId']])
                            )
                            {
                                $thisEpisode = $episodes[$value['value']['episodeId']];
                                $activityValue[] = array(
                                    'mediaId' => $value['value']['mediaId'],
                                    'seasons' => array(
                                        $thisEpisode['season'] => array(
                                            $value['value']['episodeId'] => $thisEpisode['episode']
                                        )
                                    )
                                );
                            }
                            else
                            {
                                $activityValue[] = array(
                                    'mediaId' => $value['value']['mediaId'],
                                    'seasons' => array(
                                        1 => array(
                                            $value['value']['episodeId'] => $value['value']['episodeId']
                                        )
                                    )
                                );
                            }
                        }

                        $activity->setValue($activityValue);
                        $this->em->persist($activity);
                        $this->em->flush();
                        echo ' => media added to activity: #'.$activity->getId()."\n";
                        return true;
                    }
                }
                break;
            default:
                break;
        }
        echo 'Activity '.$value['key'].' not found => droped'."\n";
        return true; // Retry
    }
}