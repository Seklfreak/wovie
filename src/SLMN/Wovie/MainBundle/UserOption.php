<?php

namespace SLMN\Wovie\MainBundle;

use SLMN\Wovie\MainBundle\Entity\UserOption as UserOptionEntity;

class UserOption
{
    private $context;
    private $em;
    private $repo;

    public function __construct(\Doctrine\ORM\EntityManager $em, \Symfony\Component\Security\Core\SecurityContext $context) {
        $this->context = $context;
        $this->em = $em;
        $this->repo = $this->em->getRepository('SLMNWovieMainBundle:UserOption');
    }

    public function get($key, $default=null)
    {
        if ($this->context->getToken() == null)
        {
            return $default;
        }

        $option = $this->repo->findOneBy(
            array(
                'key' => $key,
                'createdBy' => $this->context->getToken()->getUser()
            )
        );

        if (!$option)
        {
            if ($default === null)
            {
                return null;
            }
            else
            {
                return $default;
            }
        }

        return $option->getValue();
    }

    public function set($key, $value)
    {
        $option = $this->repo->findOneBy(
            array(
                'key' => $key,
                'createdBy' => $this->context->getToken()->getUser()
            )
        );

        if (!$option)
        {
            $option = new UserOptionEntity();
            $option->setKey($key);
            $option->setValue(strval($value));
            $option->setCreatedBy($this->context->getToken()->getUser());
        }
        else
        {
            $option->setValue($value);
        }

        $this->em->persist($option);
        $this->em->flush();
        return true;
    }
}