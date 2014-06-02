<?php

namespace SLMN\Wovie\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="omdb")
 */
class Omdb
{
    /**
     * @ORM\Column(type="string", length=9)
     * @ORM\Id
     */
    protected $imdbId; // imdbID

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $rating; // imdbRating

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $posterImage; // Poster

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $plot; // Plot

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
    public function getPlot()
    {
        return $this->plot;
    }

    /**
     * @param mixed $plot
     */
    public function setPlot($plot)
    {
        $this->plot = $plot;
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
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }
}