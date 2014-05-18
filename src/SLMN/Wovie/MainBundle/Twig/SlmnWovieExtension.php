<?php

namespace SLMN\Wovie\MainBundle\Twig;

class SlmnWovieExtension extends \Twig_Extension
{
    protected $cacheHandler;

    public function __construct($cacheHandler)
    {
        $this->cacheHandler = $cacheHandler;
    }

    public function getFunctions()
    {
        return array(
            'wovieRevision' => new \Twig_Function_Method($this, 'wovieRevisionFunction'),
        );
    }

    public function wovieRevisionFunction()
    {
        if (false === ($revision = $this->cacheHandler->fetch('twig_extension_wovie_revision'))) {
            $revision = shell_exec('git log --pretty=format:%h -n 1');
            $this->cacheHandler->save('twig_extension_wovie_revision', $revision, 86400); // 86400 seconds = 1 day
        }

        return $revision;
    }

    public function getName()
    {
        return 'slmn_wovie_extension';
    }
}