<?php

namespace SLMN\Wovie\MainBundle\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SlmnWovieExtension extends \Twig_Extension
{
    protected $em;
    protected $context;
    protected $cacheHandler;
    protected $userOptions;
    protected $router;
    protected $rootDir;

    public function __construct(
        \Doctrine\ORM\EntityManager $em,
        \Symfony\Component\Security\Core\SecurityContext $context,
        $cacheHandler,
        $userOptions,
        UrlGeneratorInterface $router,
        $rootDir
    )
    {
        $this->em = $em;
        $this->context = $context;
        $this->cacheHandler = $cacheHandler;
        $this->userOptions = $userOptions;
        $this->router = $router;
        $this->rootDir = $rootDir;
    }

    public function getFunctions()
    {
        return array(
            'getMyMovies' => new \Twig_Function_Method($this, 'getMyMoviesFunction'),
            'viewsOfId' => new \Twig_Function_Method($this, 'viewsOfId'),
            'viewsOfSeries' => new \Twig_Function_Method($this, 'viewsOfSeries'),
            'wovieRevision' => new \Twig_Function_Method($this, 'wovieRevisionFunction'),
            'wovieVersion' => new \Twig_Function_Method($this, 'wovieVersionFunction'),
            'getUserOption' => new \Twig_Function_Method($this, 'getUserOptionFunction'),
            'setUserOption' => new \Twig_Function_Method($this, 'setUserOptionFunction'),
            'getGravatarUrl' => new \Twig_Function_Method($this, 'getGravatarUrlFunction', array('is_safe' => array('html'))),
            'countMedia' => new \Twig_Function_Method($this, 'countMediaFunction'),
            'getProfile' => new \Twig_Function_Method($this, 'getProfileFunction'),
            'getFollowers' => new \Twig_Function_Method($this, 'getFollowersFunction'),
            'getFollowings' => new \Twig_Function_Method($this, 'getFollowingsFunction'),
            'isFollowing' => new \Twig_Function_Method($this, 'isFollowingFunction'),
            'isInMyLibrary' => new \Twig_Function_Method($this, 'isInMyLibraryFunction'),
            'getMediaById' => new \Twig_Function_Method($this, 'getMediaByIdFunction'),
            'getUserById' => new \Twig_Function_Method($this, 'getUserByIdFunction'),
            'timeAgo' => new \Twig_Function_Method($this, 'timeAgoFunction'),
            'getFriendsThatHaveMedia' => new \Twig_Function_Method($this, 'getFriendsThatHaveMediaFunction'),
            'getEnabledBroadcasts' => new \Twig_Function_Method($this, 'getEnabledBroadcastsFunction'),
            'hasSeenBroadcast' => new \Twig_Function_Method($this, 'hasSeenBroadcastFunction'),
            'hasPublicProfile' => new \Twig_Function_Method($this, 'hasPublicProfileFunction')
        );
    }

    public function hasPublicProfileFunction($user=null)
    {
        $userOptionsRepo = $this->em->getRepository('SLMNWovieMainBundle:UserOption');

        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }

        $publicProfileBool = $userOptionsRepo->findOneBy(array(
            'createdBy' => $user,
            'key' => 'publicProfile'
            ));

        if ($publicProfileBool && $publicProfileBool->getValue() == true)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function hasSeenBroadcastFunction($broadcast, $user=null)
    {
        if (!$this->context->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            return false;
        }

        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }

        $redisKey = 'broadcast:'.$broadcast->getId().':seenBy:user:'.$user->getId();
        if($this->cacheHandler->get($redisKey))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getEnabledBroadcastsFunction()
    {
        $broadcastsRepo = $this->em->getRepository('SLMNWovieMainBundle:Broadcast');
        $broadcasts = $broadcastsRepo->findBy(array('enabled' => true));
        return $broadcasts;
    }

    public function getFriendsThatHaveMediaFunction($media, $user=null)
    {
        if ($media->getFreebaseId())
        {
            if (!$user)
            {
                $user = $this->context->getToken()->getUser();
            }
            $followsRepo = $this->em->getRepository('SLMNWovieMainBundle:Follow');
            $mediasRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
            $followings = $followsRepo->findBy(array('user' => $user), array('createdAt' => 'DESC'));

            foreach ($followings as $key=>$following)
            {
                $followings[$key] = $following->getFollow();
                if (!$mediasRepo->findOneBy(array('createdBy' => $followings[$key], 'freebaseId' => $media->getFreebaseId())))
                {
                    unset($followings[$key]);
                }
            }
            return $followings;
        }
        else
        {
            return null;
        }
    }

    public function timeAgoFunction($datetime, $fallback='Y-m-d H:i')
    {
        $now = new \DateTime();
        $diff = $now->diff($datetime);
        if ($diff->days > 0)
        {
            return $datetime->format($fallback);
        }
        else
        {
            if ($diff->h > 0)
            {
                return ($diff->h == 1) ? '1 hour ago' : $diff->h.' hours ago';
            }
            elseif($diff->i > 0)
            {
                return ($diff->i == 1) ? '1 minute ago' : $diff->i.' minutes ago';
            }
            else
            {
                return ($diff->s == 1) ? '1 seconds ago' : $diff->s.' seconds ago';
            }
        }
    }

    public function getMediaByIdFunction($mediaId)
    {
        $mediasRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
        return $mediasRepo->findOneById($mediaId);
    }

    public function getUserByIdFunction($userId)
    {
        $usersRepo = $this->em->getRepository('SeklMainUserBundle:User');
        return $usersRepo->findOneById($userId);
    }

    public function getFollowersFunction($user=null)
    {
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        $followsRepo = $this->em->getRepository('SLMNWovieMainBundle:Follow');
        $followers = $followsRepo->findBy(array('follow' => $user), array('createdAt' => 'DESC'));
        return $followers;
    }

    public function getFollowingsFunction($user=null)
    {
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        $followsRepo = $this->em->getRepository('SLMNWovieMainBundle:Follow');
        $followings = $followsRepo->findBy(array('user' => $user), array('createdAt' => 'DESC'));
        return $followings;
    }

    public function isFollowingFunction($search, $user=null)
    {
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        $followsRepo = $this->em->getRepository('SLMNWovieMainBundle:Follow');
        if (!$followsRepo->findOneBy(array('user' => $user, 'follow' => $search)))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function getProfileFunction($user=null)
    {
        $profilesRepo = $this->em->getRepository('SLMNWovieMainBundle:Profile');
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        return $profilesRepo->findOneByUser($user);
    }

    public function countMediaFunction($user=null, $type=null)
    {
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        $moviesRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
        $query = array('createdBy' => $user);
        if ($type != null)
        {
            $query['mediaType'] = intval($type);
        }
        return count($moviesRepo->findBy($query));
    }

    public function getGravatarUrlFunction($user, $size=200)
    {
        $hash = md5(strtolower(trim($user->getEmail())));
        return $this->router->generate('slmn_wovie_image_gravatar', array('hash' => $hash, 'size' => intval($size)));
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

    public function getMyMoviesFunction($range=null, $limit=4)
    {
        $user = $this->context->getToken()->getUser();
        $moviesRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');

        if ($user == null)
        {
            return array();
        }
        else
        {
            if ($range == null)
            {
                return $moviesRepo->findByCreatedBy($user, array('title' => 'ASC'));
            }
            else
            {
                $timeStart = new \DateTime();
                $timeStart->modify($range);

                $query = $moviesRepo->createQueryBuilder('media')
                    ->select('media.id')
                    ->where('media.createdBy = :user')
                    ->setParameters(array(
                        'user' => $user
                    ))
                    ->getQuery();
                $myMedias = $query->getResult();
                $viewsRepo = $this->em->getRepository('SLMNWovieMainBundle:View');

                $query = $viewsRepo->createQueryBuilder('view')
                    ->where('view.media IN (:myMedias)')
                    ->andWhere('view.createdAt > :timeStart')
                    ->setParameters(array(
                        'myMedias' => $myMedias,
                        'timeStart' => $timeStart
                    ))
                    ->orderBy('view.createdAt', 'DESC')
                    ->groupBy('view.media')
                    ->setMaxResults($limit)
                    ->getQuery();
                $result = $query->getResult();
                $lastSeen = array();
                foreach ($result as $view)
                {
                    $lastSeen[] = $view->getMedia();
                }
                return $lastSeen;
            }
        }
    }

    public function isInMyLibraryFunction($freebaseId, $user=null)
    {
        if (!$user)
        {
            $user = $this->context->getToken()->getUser();
        }
        $mediasRepo = $this->em->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediasRepo->findOneBy(array('createdBy' => $user, 'freebaseId' => $freebaseId));
        return $media;
    }

    public function wovieVersionFunction()
    {
        if (!($version = $this->cacheHandler->get('wovie:version:plain'))) {
            $version = file_get_contents($this->rootDir.'/../VERSION');
            $this->cacheHandler->set('wovie:version:plain', $version); // 1 day
            $this->cacheHandler->expire('wovie:version:plain', 86400);
        }

        return $version;
    }

    public function wovieRevisionFunction()
    {
        if (!($revision = $this->cacheHandler->get('wovie:revision:plain'))) {
            $revision = shell_exec('git log --pretty=format:%h -n 1');
            $this->cacheHandler->set('wovie:revision:plain', $revision); // 1 day
            $this->cacheHandler->expire('wovie:revision:plain', 86400);
        }

        return $revision;
    }

    public function getFilters()
    {
        return array(
            'autolink' => new \Twig_Filter_Method($this, 'autolinkFilter', array('is_safe' => array('html'))),
            'linkList2search' => new \Twig_Filter_Method($this, 'linkList2searchFilter', array('is_safe' => array('html')))
        );
    }

    public function linkList2searchFilter($list, $prefix)
    {
        $list = htmlspecialchars($list);
        $string = null;
        $i = 0;
        foreach (explode(',', $list) as $item)
        {
            if ($i > 0)
            {
                $string .= ', ';
            }
            $string .= '<a href="'.$this->router->generate('slmn_wovie_user_search', array('q' => $prefix.':'.trim($item))).'">'.$item.'</a>';
            $i++;
        }
        return $string;
    }

    public function autolinkFilter($string)
    {
        $string = htmlspecialchars($string);

        $string = ' ' . $string;
        $string = preg_replace(
            '`([^"=\'>])(((http|https)://|www.)[^\s<]+[^\s<\.)])`i',
            '$1<a target="_blank" rel="nofollow" href="$2">$2</a>',
            $string
        );
        $string = substr($string, 1);
        $string = preg_replace('`href=\"www`','href="http://www',$string);

        return $string;
    }

    public function getName()
    {
        return 'slmn_wovie_extension';
    }
}
