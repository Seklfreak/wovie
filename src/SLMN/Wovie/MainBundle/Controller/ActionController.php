<?php

namespace SLMN\Wovie\MainBundle\Controller;

use SLMN\Wovie\MainBundle\Entity\Follow;
use SLMN\Wovie\MainBundle\Entity\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ActionController extends Controller
{
    public function searchExternalAction(Request $request)
    {
        $query = trim($request->query->get('q'));

        $mediaApi = $this->get('media_api');
        $result = $mediaApi->search('(all (any name:"'.$query.'" alias:"'.$query.'") (any type:/film/film type:/tv/tv_program))');

        if (is_array($result))
        {
            foreach ($result as $key=>$item)
            {
                if (array_key_exists('imdbId', $item))
                {
                    $em = $this->getDoctrine()->getManager();
                    $moviesRepo = $em->getRepository('SLMNWovieMainBundle:Media');

                    if ($moviesRepo->findBy(array(
                        'createdBy' => $this->getUser(),
                        'imdbId' => $item['imdbId']
                    )))
                    {
                        unset($result[$key]);
                    }
                }
            }
        }

        return $this->render(
            'SLMNWovieMainBundle:html/ajax:searchExternalResult.html.twig',
            array(
                'media' => $result
            )
        );
    }
    public function fetchDescriptionAction(Request $request)
    {
        $id = trim($request->query->get('id'));

        $mediaApi = $this->get('media_api');
        $result = $mediaApi->fetchDescription($id);

        if ($result == false)
        {
            $result = '<div class="row" style="margin-top: 5px;"></div>';
        }
        else
        {
            if (strlen($result) > 300)
            {
                $result = substr($result, 0, 300).'…';
            }
            $result = '<p class="result-plot">'.$result.'</p>';
        }

        return new Response(
            $result,
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    public function watchedItAction(Request $request)
    {
        $response = new JsonResponse();
        if (($mediaId=intval($request->get('media_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                if ($media->getMediaType() == 1)
                {
                    $newView = new View();
                    $newView->setCreatedAt(new \DateTime());
                    $newView->setMedia($media);
                    $em->persist($newView);
                    $em->flush();

                    $response->setData(array(
                        'status' => 'success'
                    ));
                }
                else
                {
                    if (($episodeIds=$request->get('episode_ids')) != null)
                    {
                        foreach ($episodeIds as $episodeId)
                        {
                            $newView = new View();
                            $newView->setCreatedAt(new \DateTime());
                            $newView->setMedia($media);
                            $newView->setEpisode(intval($episodeId));
                            $em->persist($newView);
                        }
                        $em->flush();
                        $response->setData(array(
                            'status' => 'success'
                        ));
                    }
                    else
                    {
                        $response->setData(array(
                            'status' => 'error'
                        ));
                    }
                }
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function watchedItNotAction(Request $request)
    {
        $response = new JsonResponse();
        if (($mediaId=intval($request->get('media_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $viewsRepo = $em->getRepository('SLMNWovieMainBundle:View');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                if ($media->getMediaType() == 1)
                {
                    $watches = $viewsRepo->findBy(
                        array(
                            'media' => $media
                        ),
                        array(
                            'createdAt' => 'DESC'
                        )
                    );
                    if (array_key_exists(0, $watches) && $watches[0] != null) {
                        $em->remove($watches[0]);
                    }

                    $em->flush();
                    $response->setData(array(
                        'status' => 'success'
                    ));
                }
                else
                {
                    if (($episodeIds=$request->get('episode_ids')) != null)
                    {
                        foreach ($episodeIds as $episodeId)
                        {
                            $watches = $viewsRepo->findBy(
                                array(
                                    'media' => $media,
                                    'episode' => intval($episodeId)
                                ),
                                array(
                                    'createdAt' => 'DESC'
                                )
                            );
                            if (array_key_exists(0, $watches) && $watches[0] != null)
                            {
                                $em->remove($watches[0]);
                            }
                        }
                        $em->flush();
                        $response->setData(array(
                            'status' => 'success'
                        ));
                    }
                    else
                    {
                        $response->setData(array(
                            'status' => 'error'
                        ));
                    }
                }
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function mediaDeleteAction(Request $request)
    {
        $response = new JsonResponse();
        if (($mediaId=intval($request->get('media_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $viewsRepo = $em->getRepository('SLMNWovieMainBundle:View');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                $views = $viewsRepo->findByMedia($media);
                $em->remove($media);
                foreach ($views as $view)
                {
                    $em->remove($view);
                }
                $em->flush();
                $response->setData(array(
                    'status' => 'success'
                ));
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function userFollowAction(Request $request)
    {
        $response = new JsonResponse();
        if (($userId=intval($request->get('userId'))) != null)
        {
            if ($this->getUser())
            {
                $em = $this->getDoctrine()->getManager();
                $followsRepo = $em->getRepository('SLMNWovieMainBundle:Follow');
                $usersRepo = $em->getRepository('SeklMainUserBundle:User');
                if (($doFollow=$usersRepo->findOneById(intval($userId))))
                {
                    if (!$followsRepo->findOneBy(array('user' => $this->getUser(), 'follow' => $doFollow)))
                    {
                        $follow = new Follow();
                        $follow->setUser($this->getUser());
                        $follow->setFollow($doFollow);
                        $follow->setCreatedAt(new \DateTime());
                        $em->persist($follow);
                        $em->flush();

                        $response->setData(array(
                            'status' => 'success'
                        ));
                    }
                    else
                    {
                        $response->setData(array(
                            'status' => 'error'
                        ));
                    }
                }
                else
                {
                    $response->setData(array(
                        'status' => 'error'
                    ));
                }
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function userDefollowAction(Request $request)
    {
        $response = new JsonResponse();
        if (($userId=intval($request->get('userId'))) != null)
        {
            if ($this->getUser())
            {
                $em = $this->getDoctrine()->getManager();
                $followsRepo = $em->getRepository('SLMNWovieMainBundle:Follow');
                $usersRepo = $em->getRepository('SeklMainUserBundle:User');
                if (($doFollow=$usersRepo->findOneById(intval($userId))))
                {
                    if (($follow=$followsRepo->findOneBy(array('user' => $this->getUser(), 'follow' => $doFollow))))
                    {
                        $em->remove($follow);
                        $em->flush();

                        $response->setData(array(
                            'status' => 'success'
                        ));
                    }
                    else
                    {
                        $response->setData(array(
                            'status' => 'error'
                        ));
                    }
                }
                else
                {
                    $response->setData(array(
                        'status' => 'error'
                    ));
                }
            }
            else
            {
                $response->setData(array(
                    'status' => 'error'
                ));
            }
        }
        else
        {
            $response->setData(array(
                'status' => 'error'
            ));
        }
        return $response;
    }

    public function infiniteActivityAction($page)
    {
        $activitiesRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Activity');
        $activities = $activitiesRepo->findAllForUser($this->getUser(), $page-1);

        $mediaApi = $this->get('mediaApi');
        foreach ($activities as $key=>$activity)
        {
            switch ($activity['key'])
            {
                case 'view.added':
                    if ($activity['value']['episodeId'] && $activity['value']['media']->getFreebaseId())
                    {
                        $episodes = $activity['value']['media']->getEpisodes();
                        if (array_key_exists($activity['value']['episodeId'], $episodes))
                        {
                            $activities[$key]['value']['episode'] = $episodes[$activity['value']['episodeId']];
                        }
                    }
                    break;
                default:
                    breaK;
            }
        }
        // Merge together
        $activitiesTimeMerged = array(
            'view.added' => array(),
            'media.added' => array(),
            'follow.added' => array()
        );
        foreach ($activities as $activity) {
            $addedToTimeArray = false;
            if (array_key_exists($activity['user']->getId(), $activitiesTimeMerged[$activity['key']])) {
                foreach ($activitiesTimeMerged[$activity['key']][$activity['user']->getId()] as $timestamp => $timeArray) {
                    $time = new \DateTime();
                    $time->setTimestamp($timestamp);
                    // In one hour time range?
                    if (
                        abs($timestamp - $activity['createdAt']->getTimestamp()) < 1800 // &&
                    ) {
                        $activitiesTimeMerged[$activity['key']][$activity['user']->getId()][$timestamp][] = $activity;
                        $addedToTimeArray = true;
                        break;
                    }
                }
            }
            if ($addedToTimeArray == false) {
                $activitiesTimeMerged[$activity['key']][$activity['user']->getId()][$activity['createdAt']->getTimestamp()][] = $activity;
            }
        }

        return $this->render(
            'SLMNWovieMainBundle:html/ajax/infinite:activity.html.twig',
            array(
                //'activities' => $activities,
                'page' => $page,
                'activities' => $activitiesTimeMerged
            )
        );
    }

    public function detailsSmallAction(Request $request, $id)
    {
        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media || $media->getCreatedBy() != $this->getUser())
        {
            throw $this->createNotFoundException('Media not found!');
        }

        return $this->render(
            'SLMNWovieMainBundle:html/ajax:detailsSmall.html.twig',
            array(
                'media' => $media
            )
        );
    }

    public function detailsBigAction(Request $request, $id)
    {
        $public = intval($request->get('public'));
        if ($public && $public == 1)
        {
            $public = true;
        }
        else
        {
            $public = false;
        }

        $mediasRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneById($id);

        if (!$media || $media->getCreatedBy() != $this->getUser())
        {
            throw $this->createNotFoundException('Media not found!');
        }

        return $this->render(
            'SLMNWovieMainBundle:html/ajax:detailsBig.html.twig',
            array(
                'media' => $media,
                'public' => $public
            )
        );
    }
}