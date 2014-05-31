<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    public function coverImageAction($freebaseId)
    {
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
                # TODO: Check for imdb image
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

                if ($image != null)
                {
                    file_put_contents($path.$filename, $image);
                }
            }
        }

        # Fallback to placeholder
        if ($image == null)
        {
            // TODO: Read placeholder
            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, 'http://placehold.it/400x600&text=No+cover!'); // TODO: From parameter
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->container->get('kernel')->getEnvironment());
            $image = curl_exec($curl_handle);
            curl_close($curl_handle);
        }

        $response->setPublic();
        $response->setMaxAge(604800); # 7 Days
        $response->setContent($image);
        $response->headers->set('Content-Type', 'image/jpeg');
        return $response;

        /*
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://ia.media-imdb.com/images/M/'.$id.'.jpg');
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->container->get('kernel')->getEnvironment());
        $image = curl_exec($curl_handle);
        curl_close($curl_handle);*/
    }
} 