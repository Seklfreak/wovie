<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SLMN\Wovie\MainBundle\Entity\MediaListViewRepository")
 * @ORM\Table(name="medialistview", indexes={@ORM\Index(name="medialist_idx", columns={"medialist_id"})})
 */
class MediaListView
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="MediaList", inversedBy="views")
     * @ORM\JoinColumn(name="medialist_id", referencedColumnName="id")
     */
    protected $medialist;

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
     * Gets the value of medialist.
     *
     * @return mixed
     */
    public function getMedialist()
    {
        return $this->medialist;
    }
    
    /**
     * Sets the value of medialist.
     *
     * @param mixed $medialist the medialist 
     *
     * @return self
     */
    public function setMedialist($medialist)
    {
        $this->medialist = $medialist;

        return $this;
    }
}
