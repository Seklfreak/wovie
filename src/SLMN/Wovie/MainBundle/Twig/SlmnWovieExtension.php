<?php

namespace SLMN\Wovie\MainBundle\Twig;

class SlmnWovieExtension extends \Twig_Extension
{
    protected $cacheHandler;

    public function __construct($cacheHandler)
    {
        $this->cacheHandler = $cacheHandler;
        $this->cacheHandler->setNamespace('slmn_wovie_main_twig_slmnwovieextension');
    }

    public function getFunctions()
    {
        return array(
            'wovieRevision' => new \Twig_Function_Method($this, 'wovieRevisionFunction'),
        );
    }

    public function wovieRevisionFunction()
    {
        if (false === ($revision = $this->cacheHandler->fetch('revision'))) {
            $revision = shell_exec('git log --pretty=format:%h -n 1');
            $this->cacheHandler->save('revision', $revision, 86400); // 86400 seconds = 1 day
        }

        return $revision;
    }

    public function getName()
    {
        return 'slmn_wovie_extension';
    }
}