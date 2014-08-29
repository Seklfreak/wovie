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
                if (array_key_exists('mid', $item))
                {
                    $em = $this->getDoctrine()->getManager();
                    $moviesRepo = $em->getRepository('SLMNWovieMainBundle:Media');

                    if (
                    $moviesRepo->findBy(array(
                        'createdBy' => $this->getUser(),
                        'freebaseId' => $item['mid']
                    ))
                    ) {
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
                $customCoversHandle = $this->get('wovie.customCovers');
                $customCoversHandle->delete($media);
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
            $userOptionsRepo = $this->getDoctrine()->getRepository('SLMNWovieMainBundle:UserOption');
                if (($doFollow=$usersRepo->findOneById(intval($userId))))
                {
                    if (!$followsRepo->findOneBy(array('user' => $this->getUser(), 'follow' => $doFollow)))
                    {
                        $publicProfileBool = $userOptionsRepo->findOneBy(array('createdBy' => $doFollow, 'key' => 'publicProfile'));
                        if ($publicProfileBool && $publicProfileBool->getValue() == true)
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

        return $this->render(
            'SLMNWovieMainBundle:html/ajax/infinite:activity.html.twig',
            array(
                'activities' => $activities,
                'page' => $page
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

    public function toggleFavoriteAction(Request $request)
    {
        $response = new JsonResponse();
        if (($mediaId=intval($request->get('media_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                if ($media->getFavorite())
                {
                    $media->setFavorite(false);
                }
                else
                {
                    $media->setFavorite(true);
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

    public function rateAction(Request $request)
    {
        $response = new JsonResponse();
        if (
            ($mediaId=intval($request->get('media_id'))) != null &&
            ($rating=intval($request->get('rating'))) != null
        )
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                $media->setRating($rating);

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

    public function userMediaTimeAction(Request $request)
    {
        $response = new Response();
        if (($userId = intval($request->get('user_id'))) != null)
        {
            $redis = $this->container->get('snc_redis.default');
            if(($responseString=$redis->get('user:'.$userId.':totalTimeWatching:html')))
            {
                $response->setContent($responseString);
                $response->headers->set('X-Cache', 'HIT');
            }
            else
            {
                $response->headers->set('X-Cache', 'MISS');
                $em = $this->getDoctrine()->getManager();
                $usersRepo = $em->getRepository('SeklMainUserBundle:User');
                if (($myUser = $usersRepo->findOneById(intval($userId))))
                {
                    $totalTimeInMinutes = 0;
                    $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
                    $viewsRepo = $em->getRepository('SLMNWovieMainBundle:View');
                    $batchSize = 20;
                    $i = 0;
                    $query = $mediaRepo->createQueryBuilder('media')
                        ->where('media.createdBy = :user')
                        ->andWhere('media.runtime IS NOT NULL')
                        ->setParameters(array(
                            'user' => $myUser
                        ))
                        ->getQuery();
                    $iterableResult = $query->iterate();
                    foreach($iterableResult AS $row)
                    {
                        $media = $row[0];
                        if (($views=$viewsRepo->findByMedia($media)))
                        {
                            $totalTimeInMinutes += ($media->getRuntime() * count($views));
                        }
                        if (($i % $batchSize) == 0) {
                            $em->clear();
                        }
                        ++$i;
                    }
                    if ($totalTimeInMinutes <= 0)
                    {
                        $response->setContent(' spent '.$totalTimeInMinutes.' minutes watching.');
                    }
                    else
                    {
                        $dateStart = new \DateTime('@0');
                        $dateEnd = new \DateTime('@'.($totalTimeInMinutes*60));
                        $diff = $dateStart->diff($dateEnd);
                        $responseString = 'spent <b>';
                        $responseString .= ($diff->y > 0) ? $diff->y.' year'.(($diff->y == 1) ? null: 's').' ' : null;
                        $responseString .= ($diff->m > 0) ? $diff->m.' month'.(($diff->m == 1) ? null: 's').' ' : null;
                        $responseString .= ($diff->d > 0) ? $diff->d.' day'.(($diff->d == 1) ? null: 's').' ' : null;
                        $responseString .= ($diff->h > 0) ? $diff->h.' hour'.(($diff->h == 1) ? null: 's').' ' : null;
                        $responseString .= ($diff->i > 0) ? $diff->i.' minute'.(($diff->i == 1) ? null: 's').' ' : null;
                        $responseString .= '</b>watching.';
                        $response->setContent($responseString);
                        $redis->set('user:'.$userId.':totalTimeWatching:html', $responseString);
                        $redis->expire('user:'.$userId.':totalTimeWatching:html', 3600); // 1 hour
                    }
                }
                else
                {

                    $response->setContent('<i class="fa fa-warning fa-lg"></i> Error');
                }
            }
        }
        else
        {
            $response->setContent('<i class="fa fa-warning fa-lg"></i> Error');
        }

        return $response;
    }

    function uploadCoverImageAction(Request $request, $mediaId)
    {
        $response = new JsonResponse();

        $uploadCoverForm = $this->createForm('uploadCover');

        $uploadCoverForm->handleRequest($request);
        if ($request->files->has('file'))
        {
            $uploadCoverForm->submit(array('file' => $request->files->get('file')));
        }

        if ($uploadCoverForm->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $mediasRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $media = $mediasRepo->findOneById(intval($mediaId));
            if (!$media)
            {
                $response->setData(array(
                    'error' => 'Media not found!'
                ));
            }
            else if ($media->getCreatedBy() != $this->getUser())
            {
                $response->setData(array(
                    'error' => 'You are not allowed to edit this media.'
                ));
            }
            else
            {
                $path = $this->container->getParameter("kernel.cache_dir").'/wovie/customCoversTmp/';
                @mkdir($path, 0755, $recursive=true);
                $extension = $request->files->get('file')->guessExtension();
                $filename = md5(microtime()).'_'.intval($mediaId).'.'.$extension;
                $request->files->get('file')->move($path, $filename);

                $customCoversHandle = $this->get('wovie.customCovers');
                $customCoversHandle->save($media, $path.$filename);

                $em->refresh($media);
                $response->setData(array(
                    'status' => 'success',
                    'newPoster' => $media->getPosterImage()
                ));
            }
        }
        else
        {
            $response->setData(array(
                'error' => $uploadCoverForm->getErrors(true, false)->getChildren()->getChildren()->getMessage()
            ));
        }
        return $response;
    }

    public function deleteCoverImageAction(Request $request)
    {
        $response = new JsonResponse();
        if (
            ($mediaId=intval($request->get('media_id'))) != null
        )
        {
            $em = $this->getDoctrine()->getManager();
            $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
            $media = $mediaRepo->findOneById($mediaId);
            if ($media != null && $media->getCreatedBy() == $this->getUser())
            {
                $customCoversHandle = $this->get('wovie.customCovers');
                $customCoversHandle->delete($media);

                $em->refresh($media);

                $response->setData(array(
                    'status' => 'success',
                    'newPoster' => $media->getPosterImage()
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

    public function markAsSeenBroadcastAction(Request $request)
    {
        $response = new JsonResponse();
        if (($broadcastId=intval($request->get('broadcast_id'))) != null)
        {
            $em = $this->getDoctrine()->getManager();
            $broadcastRepo = $em->getRepository('SLMNWovieMainBundle:Broadcast');
            $broadcast = $broadcastRepo->findOneById($broadcastId);
            if ($broadcast != null)
            {
                $redis = $this->container->get('snc_redis.default');
                $redisKey = 'broadcast:'.$broadcast->getId().':seenBy:user:'.$this->getUser()->getId();
                if(!$redis->get($redisKey))
                {
                    $redis->set($redisKey, true);
                    $response->setData(array(
                        'status' => 'success'
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
}
