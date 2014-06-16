<?php

namespace SLMN\Wovie\MainBundle\MediaApi;

use Symfony\Component\HttpKernel\Kernel;

class MediaApi
{
    protected $apiKey;
    protected $kernel;
    protected $monolog;
    protected $limit = 50;
    protected $cacheHandler;
    protected $userOptions;
    protected $em;

    public function __construct(Kernel $kernel, $monolog, $em, $apiKey, $cacheHandler, $userOptions)
    {
        $this->apiKey = $apiKey;
        $this->monolog = $monolog;
        $this->kernel = $kernel;
        $this->em = $em;
        $this->cacheHandler = $cacheHandler;
        $this->cacheHandler->setNamespace('slmn_wovie_main_mediaapi_mediaapi');
        $this->userOptions = $userOptions;
        $this->lang = $userOptions->get('language', 'en');
    }

    public function fetchDescription($id)
    {
        if ($this->lang == 'en')
        {
            $result = $this->search('(all (all id:"'.$id.'") (any type:/film/film type:/tv/tv_program))');
            if ($result != null && array_key_exists(0, $result))
            {
                $result = $result[0];
                if (array_key_exists('imdbId', $result))
                {
                    $omdbRepo = $this->em->getRepository('SLMNWovieMainBundle:Omdb');
                    $omdbItem = $omdbRepo->findOneByImdbId($result['imdbId']);
                    if ($omdbItem && $omdbItem->getPlot() != null)
                    {
                        return $omdbItem->getPlot();
                    }

                    $curl_handle = curl_init();
                    curl_setopt($curl_handle, CURLOPT_URL, 'http://www.omdbapi.com/?i='.$result['imdbId']);
                    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
                    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/' . $this->kernel->getEnvironment());
                    $result = curl_exec($curl_handle);
                    curl_close($curl_handle);
                    $result = json_decode($result, true);
                    if ($result && array_key_exists('Plot', $result) && $result['Plot'] != 'N/A' )
                    {
                        return $result['Plot'];
                    }
                }
            }
        }
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
        }
        else
        {
            return false;
        }
    }

    public function fetchEpisodes($id, $totalCount=false)
    {
        $url = 'https://www.googleapis.com/freebase/v1/mqlread'.'?';
        $parameter = array(
            'key' => $this->apiKey,
            'lang' => '/lang/'.$this->lang,
            'query' => json_encode(array(
                'id' => $id,
                '/tv/tv_program/episodes' => array(
                    array(
                        'name' => null,
                        'season_number' => null,
                        'episode_number' => null,
                        'limit' => 10000
                    )
                )
            ))
        );

        foreach ($parameter as $key=>$value)
        {
            $url .= $key.'='.urlencode($value).'&';
        }

        $result = $this->request($url);

        if (array_key_exists('result', $result) && array_key_exists('/tv/tv_program/episodes', $result['result']))
        {
            $episodesArray = array();
            $result = $result['result']['/tv/tv_program/episodes'];
            foreach ($result as $episode)
            {
                $episodesArray[$episode['season_number']][$episode['episode_number']] = $episode['name'];
            }

            if ($totalCount != false)
            {
                $totalEpisodes = count($result);
                $totalCountArray = array();
                $curSeason = 1;
                $curEpisodeInSeason = 1;
                foreach (range(1, $totalEpisodes) as $curEpisodeTotal)
                {
                    if (!array_key_exists($curSeason, $episodesArray) || !array_key_exists($curEpisodeInSeason, $episodesArray[$curSeason]))
                    {
                        $curEpisodeInSeason = 1;
                        $curSeason++;
                    }
                    if (array_key_exists($curSeason, $episodesArray) && array_key_exists($curEpisodeInSeason, $episodesArray[$curSeason]))
                    {
                        $totalCountArray[$curEpisodeTotal] = array(
                            'name' => $episodesArray[$curSeason][$curEpisodeInSeason],
                            'season' => $curSeason,
                            'episode' => $curEpisodeInSeason
                        );
                        $curEpisodeInSeason++;
                    }
                }
                return $totalCountArray;
            }
            else
            {
                return $episodesArray;
            }
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
            '/film/film/written_by' => '[]',
            '/film/film/genre' => '[]',
            '/film/film/runtime' => '[]',
            '/film/film/runtime' => '[{ "/film/film_cut/runtime": null, "optional": true }]',
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

        if (is_array($result) && array_key_exists('result', $result))
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
                        case '/film/film/initial_release_date':
                            $myObject['type'] = 'movie';
                            $myObject['release_date'] = end($value);
                            $myObject['release_date'] = preg_replace('/^([0-9]{4}).+$/', '$1', $myObject['release_date']);
                            break;
                        case '/tv/tv_program/air_date_of_first_episode':
                            $myObject['type'] = 'series';
                            $myObject['release_date'] = end($value);
                            $myObject['release_date'] = preg_replace('/^([0-9]{4}).+$/', '$1', $myObject['release_date']);
                            break;
                        case '/tv/tv_program/air_date_of_final_episode':
                            $myObject['type'] = 'series';
                            $myObject['final_episode'] = end($value);
                            $myObject['final_episode'] = preg_replace('/^([0-9]{4}).+$/', '$1', $myObject['final_episode']);
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
                        case '/film/film/runtime':
                            $myObject['type'] = 'movie';
                            $myObject['runtime'] = end($value);
                            if (is_array($myObject['runtime']) && array_key_exists('/film/film_cut/runtime', $myObject['runtime']))
                            {
                                $myObject['runtime'] = $myObject['runtime']['/film/film_cut/runtime'];
                            }
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
                            break;
                    }
                }
                if (array_key_exists('name', $myObject))
                {
                    if (array_key_exists('imdbId', $myObject))
                    {
                        $omdbRepo = $this->em->getRepository('SLMNWovieMainBundle:Omdb');
                        $omdbItem = $omdbRepo->findOneByImdbId($myObject['imdbId']);
                        if ($omdbItem && $omdbItem->getRating() != null)
                        {
                            $myObject['imdbRating'] = $omdbItem->getRating();
                        }
                    }

                    $toReturn[] = $myObject;
                }
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
        $cacheKey = 'request_'.$this->lang.'_'.$this->limit.'_'.md5($url);
        if (false === ($result = $this->cacheHandler->fetch($cacheKey))) {
            $this->monolog->info('Running request: '.$url);

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/'.$this->kernel->getEnvironment());
            $rawResult = curl_exec($curl_handle);
            curl_close($curl_handle);
            $result = json_decode($rawResult, true);

            if (is_array($result) && !array_key_exists('error', $result)) // Do not cache error results
            {
                $this->cacheHandler->save($cacheKey, $result, 86400); // 86400 seconds = 1 day
            }
        }
        return $result;
    }
} 