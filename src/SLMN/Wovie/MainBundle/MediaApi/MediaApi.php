<?php

namespace SLMN\Wovie\MainBundle\MediaApi;

use Symfony\Component\HttpKernel\Kernel;

class MediaApi
{
    protected $apiUrl = 'https://www.googleapis.com/freebase/v1/mqlread';
    protected $apiKey;
    protected $kernel;
    protected $lang = '/lang/en'; // TODO: $this->setLang()

    public function __construct(Kernel $kernel, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->kernel = $kernel;
    }

    public function searchByName($name)
    {
        $seriesQuery = array(
            'type' => null,
            'type' => '/tv/tv_program',
            'name' => null,
            'name~=' => $name, // TODO: ~=, OR: /common/topic/alias
            'id' => null,
            '/common/topic/description' => null,
            'air_date_of_first_episode' => null,
            'air_date_of_final_episode' => null,
            'country_of_origin' => array(),
            'episode_running_time' => array(),
            'program_creator' => array(),
            'genre' => array(),
            'number_of_seasons' => null,
            'number_of_episodes' => null,
            '/common/topic/image' =>
                array(
                    array(
                        'id' => null,
                        'optional' => true,
                    ),
                ),
            '/imdb/topic/title_id' => null
        );
        $filmQuery = array(
            'type' => null,
            'type' => '/film/film',
            'name' => null,
            'name~=' => $name, // OR: /common/topic/alias
            'id' => null,
            '/common/topic/description' => null,
            'initial_release_date' => null,
            'country' => array(),
            'runtime' => array(),
            'directed_by' => array(),
            'genre' => array(),
            '/common/topic/image' =>
                array(
                    array(
                        'id' => null,
                        'optional' => true,
                    ),
                ),
            '/imdb/topic/title_id' => null
        );

        $result = array();
        $series = $this->query(array($seriesQuery));
        $films = $this->query(array($filmQuery));

        if (array_key_exists('result', $series))
        {
            $result = array_merge(
                $result,
                $series['result']
            );
        }
        if (array_key_exists('result', $films))
        {
            $result = array_merge(
                $result,
                $films['result']
            );
        }

        if (count($result) <= 0)
        {
            return false;
        }
        else
        {
            return $result;
        }
    }

    protected function query($queryArray)
    {
        // TODO: Cache result (via parameter?!)

        $parameter = array(
            'key' => $this->apiKey,
            'lang' => $this->lang,
            'query' => json_encode($queryArray)
        );

        $url = $this->apiUrl.'?';
        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.$value.'&';
        }

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->kernel->getEnvironment());
        $rawResult = curl_exec($curl_handle);
        curl_close($curl_handle);

        return json_decode($rawResult, true);
    }

    /*
    public function __construct(Kernel $kernel, $apiUrl='http://www.omdbapi.com/')
    {
        $this->apiUrl = $apiUrl;
        $this->defaultParameter = array(
            'r' => 'JSON'
        );
        $this->kernel = $kernel;
    }

    public function search($query, $lookup=false)
    {
        $result = $this->request(array(
            's' => $query
        ));
        if (!array_key_exists('Search', $result))
        {
            return false;
        }
        $result = $this->removeOtherMediaTypes($result['Search']);
        if ($lookup == false)
        {
            return $result;
        }
        else
        {
            $lookedUp = array();
            foreach ($result as $element)
            {
                $lookedUp[] = $this->lookupId($element['imdbID']);
            }
            return $lookedUp;
        }
    }

    public function lookupId($id)
    {
        $result = $this->request(array(
            'i' => $id
        ));
        return $result;
    }

    protected function removeOtherMediaTypes($list, $keep=array('movie', 'series'))
    {
        $toReturn = array();
        foreach ($list as $element)
        {
            if (array_key_exists('Type', $element) && in_array($element['Type'], $keep))
            {
                $toReturn[] = $element;
            }
        }
        return $toReturn;
    }

    protected function request($parameter)
    {
        // TODO: Cache result (via parameter?!)

        $parameter = array_merge($this->defaultParameter, $parameter);

        $url = $this->apiUrl.'?';
        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.urlencode($value).'&';
        }
        $context = stream_context_create(array(
                'http' => array(
                    'timeout' => 1
                )
            )
        );

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->kernel->getEnvironment());
        $rawResult = curl_exec($curl_handle);
        curl_close($curl_handle);
        //$rawResult = file_get_contents($url, null, $context);

        return json_decode($rawResult, true);
    }
    */
} 