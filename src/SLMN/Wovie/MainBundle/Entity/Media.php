<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="media", indexes={@ORM\Index(name="createdBy_idx", columns={"createdBy"}), @ORM\Index(name="mediaType_idx", columns={"mediaType"})})
 */
class Media
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
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $countries;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $freebaseId;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $imdbId;

    /**
     * @ORM\ManyToOne(targetEntity="Sekl\Main\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="createdBy", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $lastUpdatedAt;

    /**
     * @ORM\Column(type="integer")
     */
    protected $mediaType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $releaseYear;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $finalYear;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $runtime;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $writtenBy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $genres;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $numberOfEpisodes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $posterImage;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    protected $episodes;

    /**
     * @return mixed
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }

    /**
     * @param mixed $episodes
     */
    public function setEpisodes($episodes)
    {
        $this->episodes = $episodes;
    }

    /**
     * @return mixed
     */
    public function getLastUpdatedAt()
    {
        return $this->lastUpdatedAt;
    }

    /**
     * @param mixed $lastUpdatedAt
     */
    public function setLastUpdatedAt($lastUpdatedAt)
    {
        $this->lastUpdatedAt = $lastUpdatedAt;
    }

    /**
     * @return mixed
     */
    public function getPosterImage()
    {
        return $this->posterImage;
    }

    /**
     * @param mixed $posterImage
     */
    public function setPosterImage($posterImage)
    {
        $this->posterImage = $posterImage;
    }

    /**
     * @return mixed
     */
    public function getNumberOfEpisodes()
    {
        return $this->numberOfEpisodes;
    }

    /**
     * @param mixed $numberOfEpisodes
     */
    public function setNumberOfEpisodes($numberOfEpisodes)
    {
        $this->numberOfEpisodes = $numberOfEpisodes;
    }

    /**
     * @return mixed
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @param mixed $genres
     */
    public function setGenres($genres)
    {
        $this->genres = $genres;
    }

    /**
     * @return mixed
     */
    public function getWrittenBy()
    {
        return $this->writtenBy;
    }

    /**
     * @param mixed $writtenBy
     */
    public function setWrittenBy($writtenBy)
    {
        $this->writtenBy = $writtenBy;
    }

    /**
     * @return mixed
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * @param mixed $runtime
     */
    public function setRuntime($runtime)
    {
        $this->runtime = $runtime;
    }

    /**
     * @return mixed
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param mixed $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getFinalYear()
    {
        return $this->finalYear;
    }

    /**
     * @param mixed $finalYear
     */
    public function setFinalYear($finalYear)
    {
        $this->finalYear = $finalYear;
    }

    /**
     * @return mixed
     */
    public function getReleaseYear()
    {
        return $this->releaseYear;
    }

    /**
     * @param mixed $releaseYear
     */
    public function setReleaseYear($releaseYear)
    {
        $this->releaseYear = $releaseYear;
    }

    /**
     * @return mixed
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * @param mixed $mediaType
     */
    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;
    }

    /**
     * @return mixed
     */
    public function getFreebaseId()
    {
        return $this->freebaseId;
    }

    /**
     * @param mixed $freebaseId
     */
    public function setFreebaseId($freebaseId)
    {
        $this->freebaseId = $freebaseId;
    }

    /**
     * @return mixed
     */
    public function getImdbId()
    {
        return $this->imdbId;
    }

    /**
     * @param mixed $imdbId
     */
    public function setImdbId($imdbId)
    {
        $this->imdbId = $imdbId;
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

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
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

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}