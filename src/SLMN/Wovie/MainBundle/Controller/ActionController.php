<?php

namespace SLMN\Wovie\MainBundle\Controller;

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
}