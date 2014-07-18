<?php
namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pendingUserActivation", indexes={@ORM\Index(name="user_idx", columns={"user"}), @ORM\Index(name="tokenHash_idx", columns={"tokenHash"})})
 */
class PendingUserActivation
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sekl\Main\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $tokenHash;

    /**
     * @return mixed
     */
    public function getTokenHash()
    {
        return $this->tokenHash;
    }

    /**
     * @param mixed $tokenHash
     */
    public function setTokenHash($tokenHash)
    {
        $this->tokenHash = $tokenHash;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}