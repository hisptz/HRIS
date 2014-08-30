<?php

namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sponsor
 * @Gedmo\Loggable
 * @ORM\Table("hris_training_sponsors")
 * @ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\SponsorRepository")
 */
class Sponsor
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @var string $uid
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="uid", type="string", length=13, unique=false)
     */
    private $uid;

    /**
     * @var string $sponsorName
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="sponsorName", type="string", length=255)
     */
    private $sponsorName;

    /**
     * @var string $region
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="region", type="string", length=255)
     */
    private $region;

    /**
     * @var string $phone
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="phone", type="string", length=255)
     */
    private $phone;

     /**
     * @var string $email
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;
    /**
     * @var string $box
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="box", type="string", length=255)
     */
    private $box;


    /**
     * @var \DateTime $datecreated
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="datecreated", type="datetime", nullable=false)
     */
    private $datecreated;

    /**
     * @var \DateTime $lastupdated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="lastupdated", type="datetime", nullable=true)
     */
    private $lastupdated;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return Sponsor
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    
        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return Sponsor
     */
    public function setRegion($region)
    {
        $this->region = $region;
    
        return $this;
    }

    /**
     * Get region
     *
     * @return string 
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Sponsor
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Sponsor
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set box
     *
     * @param string $box
     * @return Sponsor
     */
    public function setBox($box)
    {
        $this->box = $box;
    
        return $this;
    }

    /**
     * Get box
     *
     * @return string 
     */
    public function getBox()
    {
        return $this->box;
    }

    /**
     * Set sponsorName
     *
     * @param string $sponsorName
     * @return Sponsor
     */
    public function setSponsorName($sponsorName)
    {
        $this->sponsorName = $sponsorName;
    
        return $this;
    }

    /**
     * Get sponsorName
     *
     * @return string 
     */
    public function getSponsorName()
    {
        return $this->sponsorName;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return Sponsor
     */
    public function setDatecreated($datecreated)
    {
        $this->datecreated = $datecreated;
    
        return $this;
    }

    /**
     * Get datecreated
     *
     * @return \DateTime 
     */
    public function getDatecreated()
    {
        return $this->datecreated;
    }

    /**
     * Set lastupdated
     *
     * @param \DateTime $lastupdated
     * @return Sponsor
     */
    public function setLastupdated($lastupdated)
    {
        $this->lastupdated = $lastupdated;
    
        return $this;
    }

    /**
     * Get lastupdated
     *
     * @return \DateTime 
     */
    public function getLastupdated()
    {
        return $this->lastupdated;
    }

    public function __toString()
    {
        return $this->sponsorName;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


    }
}