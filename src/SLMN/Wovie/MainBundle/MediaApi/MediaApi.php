<?php

namespace SLMN\Wovie\MainBundle\MediaApi;

class MediaApi
{
    protected $apiUrl;
    protected $defaultParameter;

    public function __construct($apiUrl='http://www.omdbapi.com/')
    {
        $this->apiUrl = $apiUrl;
        $this->defaultParameter = array(
            'r' => 'JSON'
        );
    }

    public function search($query, $lookup=false)
    {
        $result = $this->request(array(
            's' => $query
        ));
        $result = $this->removeOtherMediaTypes($result['Search']);
        // TODO: Error Handling! (No results)
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
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'WOVIE/0.1');
        $rawResult = curl_exec($curl_handle);
        curl_close($curl_handle);
        //$rawResult = file_get_contents($url, NULL, $context);

        return json_decode($rawResult, true);
    }
} 