<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="broadcast")
 */
class Broadcast
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $icon = 'bullhorn';

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $closeable = true;

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the value of id.
     *
     * @param mixed $id the id 
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the value of message.
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * Sets the value of message.
     *
     * @param mixed $message the message 
     *
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Gets the value of icon.
     *
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * Sets the value of icon.
     *
     * @param mixed $icon the icon 
     *
     * @return self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Gets the value of createdAt.
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Sets the value of createdAt.
     *
     * @param mixed $createdAt the created at 
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets the value of enabled.
     *
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Sets the value of enabled.
     *
     * @param mixed $enabled the enabled 
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Gets the value of closeable.
     *
     * @return mixed
     */
    public function getCloseable()
    {
        return $this->closeable;
    }
    
    /**
     * Sets the value of closeable.
     *
     * @param mixed $closeable the closeable 
     *
     * @return self
     */
    public function setCloseable($closeable)
    {
        $this->closeable = $closeable;

        return $this;
    }
}
