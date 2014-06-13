<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="follow", indexes={@ORM\Index(name="follow_idx", columns={"follow"}), @ORM\Index(name="user_idx", columns={"user"})})
 */
class Follow
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sekl\Main\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Sekl\Main\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="follow", referencedColumnName="id")
     */
    protected $follow;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @return mixed
     */
    public function getFollow()
    {
        return $this->follow;
    }

    /**
     * @param mixed $follow
     */
    public function setFollow($follow)
    {
        $this->follow = $follow;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
} 