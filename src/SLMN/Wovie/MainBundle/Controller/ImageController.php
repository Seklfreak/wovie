<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    public function coverImageAction($freebaseId)
    {
        $logger = $this->get('logger');
        $image = null;
        $path = $this->container->getParameter("kernel.cache_dir").'/wovie/covers/';
        @mkdir($path, 0755, $recursive=true);
        $filename = md5($freebaseId);
        $response = new Response();

        # Try filecache
        if ($image == null)
        {
            if (file_exists($path.$filename) && is_readable($path.$filename))
            {
                $image = file_get_contents($path.$filename);
                $logger->info('Loaded cover ('.$freebaseId.') from filecache: '.$path.$filename);
            }
        }

        # Try imdb and freebase
        if ($image == null)
        {
            $mediaApi = $this->get('media_api');
            $result = $mediaApi->search('(all (all id:"'.$freebaseId.'") (any type:/film/film type:/tv/tv_program))');
            if ($result != null && array_key_exists(0, $result))
            {
                $result = $result[0];
                if (array_key_exists('imdbId', $result))
                {
                    $omdbRepo = $this->getDoctrine()->getManager()->getRepository('SLMNWovieMainBundle:Omdb');
                    $omdbItem = $omdbRepo->findOneByImdbId($result['imdbId']);
                    if ($omdbItem && $omdbItem->getPosterImage() != null)
                    {
                        $curl_handle = curl_init();
                        curl_setopt($curl_handle, CURLOPT_URL, $omdbItem->getPosterImage());
                        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
                        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->container->get('kernel')->getEnvironment());
                        $image = curl_exec($curl_handle);
                        curl_close($curl_handle);
                    }
                }

                // Wired omdb api… not everything in the download database?
                if ($image == null)
                {
                    if (array_key_exists('imdbId', $result))
                    {
                        $curl_handle = curl_init();
                        curl_setopt($curl_handle, CURLOPT_URL, 'http://www.omdbapi.com/?i='.$result['imdbId']);
                        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
                        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->container->get('kernel')->getEnvironment());
                        $result = curl_exec($curl_handle);
                        curl_close($curl_handle);
                        $result = json_decode($result, true);
                        if ($result && array_key_exists('Poster', $result) && $result['Poster'] != 'N/A' )
                        {
                            $curl_handle = curl_init();
                            curl_setopt($curl_handle, CURLOPT_URL, $result['Poster']);
                            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
                            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->container->get('kernel')->getEnvironment());
                            $image = curl_exec($curl_handle);
                            curl_close($curl_handle);
                        }
                    }
                }

                if ($image == null)
                {
                    if (array_key_exists('poster', $result))
                    {
                        $curl_handle = curl_init();
                        curl_setopt($curl_handle, CURLOPT_URL, 'https://usercontent.googleapis.com/freebase/v1/image'.$freebaseId.'?maxwidth=400&maxheight=600&mode=fit');
                        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
                        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->container->get('kernel')->getEnvironment());
                        $image = curl_exec($curl_handle);
                        curl_close($curl_handle);
                    }
                }

                if ($image != null)
                {
                    $logger->info('Saved cover ('.$freebaseId.') to filecache: '.$path.$filename);
                    file_put_contents($path.$filename, $image);
                    $response->setMaxAge(2592000); # 1 Month
                }
                else
                {
                    $response->setMaxAge(86400); # 1 Day
                }
            }
        }

        # Fallback to placeholder
        if (empty($image))
        {
            $logger->info('Cover '.$freebaseId.' not found.');
            $image = file_get_contents($this->get('kernel')->locateResource('@SLMNWovieMainBundle/Resources/assets/placeholder.jpg'));
        }

        $response->setPublic();
        $response->setContent($image);
        $response->headers->set('Content-Type', 'image/jpeg');
        return $response;
    }
} 