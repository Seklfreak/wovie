<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="session")
 */
class Session
{
    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\Id
     */
    protected $sessionId;

    /**
     * @ORM\Column(type="text")
     */
    protected $sessionValue;

    /**
     * @ORM\Column(type="integer", length=4)
     */
    protected $sessionTime;

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return mixed
     */
    public function getSessionTime()
    {
        return $this->sessionTime;
    }

    /**
     * @param mixed $sessionTime
     */
    public function setSessionTime($sessionTime)
    {
        $this->sessionTime = $sessionTime;
    }

    /**
     * @return mixed
     */
    public function getSessionValue()
    {
        return $this->sessionValue;
    }

    /**
     * @param mixed $sessionValue
     */
    public function setSessionValue($sessionValue)
    {
        $this->sessionValue = $sessionValue;
    }
}