<?php

namespace SLMN\Wovie\MainBundle\MediaApi;

use Symfony\Component\HttpKernel\Kernel;

class MediaApi
{
    protected $apiKey;
    protected $kernel;
    protected $lang = 'en'; // TODO: $this->setLang()
    protected $limit = 50; // TODO: Option

    public function __construct(Kernel $kernel, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->kernel = $kernel;
    }

    public function fetchDescription($id)
    {
        $url = 'https://www.googleapis.com/freebase/v1/topic'.$id.'?';
        $parameter = array(
            'key' => $this->apiKey,
            'lang' => $this->lang,
            'filter' => '/common/topic/description'
        );

        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.urlencode($value).'&';
        }

        $result = $this->request($url);
        if (array_key_exists('property', $result) && array_key_exists('/common/topic/description', $result['property']))
        {
            return $result['property']['/common/topic/description']['values'][0]['value'];
            return true;
        }
        else
        {
            return false;
        }
    }

    public function search($filter)
    {

        $parameter = array(
            'key' => $this->apiKey,
            'lang' => $this->lang,
            'limit' => $this->limit,
            'filter' => $filter
        );

        $output = array(
            'mid' => 'null',
            'name' => '[]',
            '/common/topic/description' => 'null',
            '/tv/tv_program/air_date_of_first_episode' => '[]',
            '/tv/tv_program/air_date_of_final_episode' => '[]',
            '/tv/tv_program/country_of_origin' => '[]',
            '/tv/tv_program/episode_running_time' => '[]',
            '/tv/tv_program/program_creator' => '[]',
            '/tv/tv_program/genre' => '[]',
            '/tv/tv_program/number_of_seasons' => '[]',
            '/tv/tv_program/number_of_episodes' => '[]',
            '/film/film/initial_release_date' => '[]',
            '/film/film/country' => '[]',
            '/film/film_cut/runtime' => '[]',
            '/film/film/written_by' => '[]',
            '/film/film/genre' => '[]',
            '/common/topic/image' => '[{ "id": null, "optional": true }]',
            '/imdb/topic/title_id' => '[]'
        );
        $outputString = '[{';
        $i = 0;
        foreach ($output as $key => $value) {
            if ($i != 0) {
                $outputString .= ',';
            }
            $outputString .= ' "' . $key . '": ' . $value . ' ';
            $i++;
        }
        $outputString .= '}]';
        $parameter['mql_output'] = $outputString;

        $url = 'https://www.googleapis.com/freebase/v1/search'.'?';
        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.urlencode($value).'&';
        }

        $result = $this->request($url);

        if (array_key_exists('result', $result))
        {
            $toReturn = array();
            $i = 0;
            foreach ($result['result'] as $object)
            {
                $myObject = array();

                foreach ($object as $key=>$value)
                {
                    if (empty($value) || $value == array())
                    {
                        continue;
                    }
                    switch ($key)
                    {
                        case 'mid':
                            $myObject['mid'] = $value;
                            break;
                        case 'name':
                            $myObject['name'] = end($value);
                            break;
                        case '/common/topic/description':
                            $myObject['description'] = $value;
                            break;
                        case '/film/film/initial_release_date':
                            $myObject['type'] = 'movie';
                            $myObject['release_date'] = end($value);
                            break;
                        case '/tv/tv_program/air_date_of_first_episode':
                            $myObject['type'] = 'series';
                            $myObject['release_date'] = end($value);
                            break;
                        case '/tv/tv_program/air_date_of_final_episode':
                            $myObject['type'] = 'series';
                            $myObject['final_episode'] = end($value);
                            break;
                        case '/film/film/country':
                            $myObject['type'] = 'movie';
                            $myObject['countries'] = array();
                            foreach ($value as $country)
                            {
                                $myObject['countries'][] = $country;
                            }
                            break;
                        case '/tv/tv_program/country_of_origin':
                            $myObject['type'] = 'series';
                            $myObject['countries'] = array();
                            foreach ($value as $country)
                            {
                                $myObject['countries'][] = $country;
                            }
                            break;
                        case '/film/film_cut/runtime':
                            $myObject['type'] = 'movie';
                            $myObject['runtime'] = end($value);
                            $myObject['runtime'] = preg_replace('/^([0-9]+)(\.)?([0-9]+)?([\ A-Za-z]+)?$/', '$1', $myObject['runtime']);
                            break;
                        case '/tv/tv_program/episode_running_time':
                            $myObject['type'] = 'series';
                            $myObject['runtime'] = end($value);
                            $myObject['runtime'] = preg_replace('/^([0-9]+)(\.)?([0-9]+)?([\ A-Za-z]+)?$/', '$1', $myObject['runtime']);
                            break;
                        case '/film/film/written_by':
                            $myObject['type'] = 'movie';
                            $myObject['written_by'] = array();
                            foreach ($value as $directed_by)
                            {
                                $myObject['written_by'][] = $directed_by;
                            }
                            break;
                        case '/tv/tv_program/program_creator':
                            $myObject['type'] = 'series';
                            $myObject['written_by'] = array();
                            foreach ($value as $directed_by)
                            {
                                $myObject['written_by'][] = $directed_by;
                            }
                            break;
                        case '/film/film/genre':
                            $myObject['type'] = 'movie';
                            $myObjeToichi_Kurobact['genres'] = array();
                            foreach ($value as $genre)
                            {
                                $myObject['genres'][] = $genre;
                            }
                            break;
                        case '/tv/tv_program/genre':
                            $myObject['type'] = 'series';
                            $myObject['genres'] = array();
                            foreach ($value as $genre)
                            {
                                $myObject['genres'][] = $genre;
                            }
                            break;
                        case '/tv/tv_program/number_of_seasons':
                            $myObject['type'] = 'series';
                            $myObject['number_of_seasons'] = end($value);
                            break;
                        case '/tv/tv_program/number_of_episodes':
                            $myObject['type'] = 'series';
                            $myObject['number_of_episodes'] = end($value);
                            break;
                        case '/common/topic/image':
                            $myObject['poster'] = end($value)["id"];
                            break;
                        case '/imdb/topic/title_id':
                            $myObject['imdbId'] = end($value);
                            break;
                        case 'relevance:score':
                            break;
                        default:
                            //echo $key.' => '.print_r($value, true)."\n";
                            break;
                    }
                }
                $toReturn[] = $myObject;
                $i++;
            }
            return $toReturn;
        }
        else
        {
            return false;
        }
    }

    protected function request($url)
    {
        // TODO: Cache result (via url?!)

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->kernel->getEnvironment());
        $rawResult = curl_exec($curl_handle);
        curl_close($curl_handle);

        $result = json_decode($rawResult, true);
        return $result;
    }
} 