<?php

namespace SLMN\Wovie\MainBundle\Twig;

class SlmnWovieExtension extends \Twig_Extension
{
    protected $em;
    protected $context;
    protected $cacheHandler;
    protected $userOptions;

    public function __construct(
        \Doctrine\ORM\EntityManager $em,
        \Symfony\Component\Security\Core\SecurityContext $context,
        $cacheHandler,
        $userOptions
    )
    {
        $this->em = $em;
        $this->context = $context;
        $this->cacheHandler = $cacheHandler;
        $this->cacheHandler->setNamespace('slmn_wovie_main_twig_slmnwovieextension');
        $this->userOptions = $userOptions;
    }

    public function getFunctions()
    {
        return array(
            'getMyMovies' => new \Twig_Function_Method($this, 'getMyMoviesFunction'),
            'viewsOfId' => new \Twig_Function_Method($this, 'viewsOfId'),
            'viewsOfSeries' => new \Twig_Function_Method($this, 'viewsOfSeries'),
            'wovieRevision' => new \Twig_Function_Method($this, 'wovieRevisionFunction'),
            'getUserOption' => new \Twig_Function_Method($this, 'getUserOptionFunction'),
            'setUserOption' => new \Twig_Function_Method($this, 'setUserOptionFunction')
        );
    }

    public function getUserOptionFunction($key, $default=null)
    {
        return $this->userOptions->get($key, $default);
    }

    public function setUserOptionFunction($key, $value)
    {
        return $this->userOptions->set($key, $value);
    }

    public function viewsOfId($id, $episode=null)
    {
        $viewRepo = $this->em->getRepository('SLMNWovieMainBundle:View');
        if ($episode == null)
        {
            $query = $viewRepo->createQueryBuilder('view')
                ->add('select', 'COALESCE(view.episode, view.id) as unq_episode')
                ->where('view.media = :media')
                ->groupBy('unq_episode')
                ->setParameter('media', $id)
                ->getQuery();
                $views = $query->getResult();
            return count($views);
        }
        else
        {
            $query = $viewRepo->createQueryBuilder('view')
                ->where('view.media = :media')
                ->andWhere('view.episode = :episode')
                ->setParameters(array(
                    'media' => $id,
                    'episode' => $episode
                ))
                ->getQuery();
            $views = $query->getResult();
            return count($views);
        }
    }

    public function getMyMoviesFunction()
    {
        $user = $this->context->getToken()->getUser();
        $moviesRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');

        if ($user == null)
        {
            return array();
        }
        else
        {
            return $moviesRepo->findByCreatedBy($user, array('title' => 'ASC'));
        }
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