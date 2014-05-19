<?php

namespace SLMN\Wovie\MainBundle\Twig;

class SlmnWovieExtension extends \Twig_Extension
{
    protected $em;
    protected $context;
    protected $cacheHandler;

    public function __construct(\Doctrine\ORM\EntityManager $em, \Symfony\Component\Security\Core\SecurityContext $context, $cacheHandler)
    {
        $this->em = $em;
        $this->context = $context;
        $this->cacheHandler = $cacheHandler;
        $this->cacheHandler->setNamespace('slmn_wovie_main_twig_slmnwovieextension');
    }

    public function getFunctions()
    {
        return array(
            'getMyMovies' => new \Twig_Function_Method($this, 'getMyMoviesFunction'),
            'viewsOfId' => new \Twig_Function_Method($this, 'viewsOfId'),
            'viewsOfSeries' => new \Twig_Function_Method($this, 'viewsOfSeries'),
            'wovieRevision' => new \Twig_Function_Method($this, 'wovieRevisionFunction'),
        );
    }

    public function viewsOfSeries($id)
    {
        $mediaRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
        $viewRepo = $this->em->getRepository('SLMNWovieMainBundle:View');
        $media = $mediaRepo->findOneById($id);
        if ($media != null)
        {
            $query = $viewRepo->createQueryBuilder('view')
                ->where('view.media = :media')
                ->groupBy('view.episode')
                ->setParameter('media', $media)
                ->getQuery();

            $views = $query->getResult();
            return count($views);
        }
        else
        {
            return false;
        }
    }

    public function viewsOfId($id, $episode=null)
    {
        $mediaRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
        $viewRepo = $this->em->getRepository('SLMNWovieMainBundle:View');
        $media = $mediaRepo->findOneById($id);
        if ($media != null)
        {
            if ($media->getMediaType() == 1) {
                $views = $viewRepo->findByMedia($media);
                return count($views);
            } else {
                $query = $viewRepo->createQueryBuilder('view')
                    ->where('view.media = :media')
                    ->andWhere('view.episode = :episode')
                    ->setParameters(array(
                        'media' => $media,
                        'episode' => $episode
                    ))
                    ->getQuery();

                $views = $query->getResult();
                return count($views);
            }
        }
        else
        {
            return false;
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
            return $moviesRepo->findByCreatedBy($user);
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