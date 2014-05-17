<?php

namespace SLMN\Wovie\MainBundle\MediaApi;

use Symfony\Component\HttpKernel\Kernel;

class MediaApi
{
    protected $apiUrl = 'https://www.googleapis.com/freebase/v1/search';
    protected $apiKey;
    protected $kernel;
    protected $lang = 'en'; // TODO: $this->setLang()
    protected $limit = 50; // TODO: Option

    public function __construct(Kernel $kernel, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->kernel = $kernel;
    }

    public function searchByName($name, $filter)
    {

        $result = $this->search($name, $filter);
        //$result = array();
        //$series = $this->query(array($seriesQuery));
        //$films = $this->query(array($filmQuery));

        var_dump($result);

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

    public function search($filter, $output=false)
    {
        // TODO: Cache result (via parameter?!)

        $parameter = array(
            'key' => $this->apiKey,
            'lang' => $this->lang,
            'limit' => $this->limit,
            'filter' => $filter,
            //'mql_output' => '[{ "/film/film/country": [] }]'
        );

        if ($output != false)
        {
            $outputString = '(';
            foreach ($output as $element)
            {
                $outputString .= ' '.$element;
            }
            $outputString .= ')';
            $parameter['output'] = $outputString;
        }

        $url = $this->apiUrl.'?';
        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.urlencode($value).'&';
        }

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->kernel->getEnvironment());
        $rawResult = curl_exec($curl_handle);
        curl_close($curl_handle);

        $result = json_decode($rawResult, true);

        var_dump($url);
        var_dump($result);

        if (array_key_exists('result', $result))
        {
            $toReturn = array();
            $i = 0;
            foreach ($result['result'] as $object)
            {
                $myObject = array();

                $myObject['mid'] = $object['mid'];
                $myObject['score'] = $object['score'];

                foreach ($object['output'] as $key=>$value)
                {
                    if (empty($value))
                    {
                        continue;
                    }
                    switch ($key)
                    {
                        case 'name':
                            $myObject['name'] = end($value['/type/object/name']);
                            break;
                        case '/common/topic/description':
                            $myObject['description'] = end($value['/common/topic/description']);
                            break;
                        case '/film/film/initial_release_date':
                            $myObject['release_date'] = end($value['/film/film/initial_release_date']);
                            break;
                        case '/tv/tv_program/air_date_of_first_episode':
                            $myObject['release_date'] = end($value['/tv/tv_program/air_date_of_first_episode']);
                            break;
                        case '/tv/tv_program/air_date_of_final_episode':
                            $myObject['final_episode'] = end($value['/tv/tv_program/air_date_of_final_episode']);
                            break;
                        case '/film/film/country':
                            $myObject['countries'] = array();
                            foreach ($value['/film/film/country'] as $country)
                            {
                                $myObject['countries'][] = $country['name'];
                            }
                            break;
                        case '/tv/tv_program/country_of_origin':
                            $myObject['countries'] = array();
                            foreach ($value['/tv/tv_program/country_of_origin'] as $country)
                            {
                                $myObject['countries'][] = $country['name'];
                            }
                            break;
                        case '/film/film_cut/runtime':
                            $myObject['runtime'] = end($value['/film/film_cut/runtime']);
                            $myObject['runtime'] = preg_replace('/^([0-9]+)(\.)?([0-9]+)?([\ A-Za-z]+)?$/', '$1', $myObject['runtime']);
                            break;
                        case '/tv/tv_program/episode_running_time':
                            $myObject['runtime'] = end($value['/tv/tv_program/episode_running_time']);
                            $myObject['runtime'] = preg_replace('/^([0-9]+)(\.)?([0-9]+)?([\ A-Za-z]+)?$/', '$1', $myObject['runtime']);
                            break;
                        case '/film/film/written_by':
                            $myObject['written_by'] = array();
                            foreach ($value['/film/film/written_by'] as $directed_by)
                            {
                                $myObject['written_by'][] = $directed_by['name'];
                            }
                            break;
                        case '/tv/tv_program/program_creator':
                            $myObject['written_by'] = array();
                            foreach ($value['/tv/tv_program/program_creator'] as $directed_by)
                            {
                                $myObject['written_by'][] = $directed_by['name'];
                            }
                            break;
                        case '/film/film/genre':
                            $myObjeToichi_Kurobact['genres'] = array();
                            foreach ($value['/film/film/genre'] as $genre)
                            {
                                $myObject['genres'][] = $genre['name'];
                            }
                            break;
                        case '/tv/tv_program/genre':
                            $myObject['genres'] = array();
                            foreach ($value['/tv/tv_program/genre'] as $genre)
                            {
                                $myObject['genres'][] = $genre['name'];
                            }
                            break;
                        case '/tv/tv_program/number_of_seasons':
                            $myObject['number_of_seasons'] = end($value['/tv/tv_program/number_of_seasons']);
                            break;
                        case '/tv/tv_program/number_of_episodes':
                            $myObject['number_of_episodes'] = end($value['/tv/tv_program/number_of_episodes']);
                            break;
                        case '/common/topic/image':
                            $myObject['poster'] = end($value['/common/topic/image'])['mid'];
                            break;
                        case '/imdb/topic/title_id':
                            $myObject['imdbId'] = end($value['/imdb/topic/title_id']);
                            break;
                        default:
                            echo $key.' => '.print_r($value, true)."\n";
                            break;
                    }
                }
                //var_dump($myObject);
                $toReturn[] = $myObject;
                $i++;
            }
            return $toReturn;
        }
        else
        {
            //var_dump($result);
            return false;
        }
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