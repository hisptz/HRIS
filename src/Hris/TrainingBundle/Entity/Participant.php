<?php

namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Participant
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_participants")
 * @ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\ParticipantRepository")
 */
class Participant
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
     * @var string $username
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="username", type="string", length=64)
     */
    private $username;

    /**
     * @var String $firstname
     * @Gedmo\Versioned
     * @ORM\Column(name="firstname", type="string")
     */
    private $firstname;

    /**
     * @var String $middlename
     * @Gedmo\Versioned
     * @ORM\Column(name="middlename", type="string")
     */
    private $middlename;

    /**
     * @var String $lastname
     * @Gedmo\Versioned
     * @ORM\Column(name="lastname", type="string")
     */
    private $lastname;


    /**
     * @var String $currentJobResponsibility
     * @Gedmo\Versioned
     * @ORM\Column(name="currentJobResponsibility", type="string")
     */
    private $currentJobResponsibility;



/**
     * @var String $currentJobTitle
     * @Gedmo\Versioned
     * @ORM\Column(name="currentJobTitle", type="string")
     */
    private $currentJobTitle;

/**
     * @var String $qualificationAndEmployement
     * @Gedmo\Versioned
     * @ORM\Column(name="qualificationAndEmployement", type="string")
     */
    private $qualificationAndEmployement;

/**
     * @var String $town
     * @Gedmo\Versioned
     * @ORM\Column(name="town", type="string")
     */
    private $town;

/**
     * @var String $district
     * @Gedmo\Versioned
     * @ORM\Column(name="district", type="string")
     */
    private $district;

/**
     * @var String $region
     * @Gedmo\Versioned
     * @ORM\Column(name="region", type="string")
     */
    private $region;


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
     * Set firstname
     *
     * @param string $firstname
     * @return Participant
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set middlename
     *
     * @param string $middlename
     * @return Participant
     */
    public function setMiddlename($middlename)
    {
        $this->middlename = $middlename;

        return $this;
    }

    /**
     * Get middlename
     *
     * @return string
     */
    public function getMiddlename()
    {
        return $this->middlename;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Participant
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set currentJobResponsibility
     *
     * @param string $currentJobResponsibility
     * @return Participant
     */
    public function setCurrentJobResponsibility($currentJobResponsibility)
    {
        $this->currentJobResponsibility = $currentJobResponsibility;

        return $this;
    }

    /**
     * Get currentJobResponsibility
     *
     * @return string
     */
    public function getCurrentJobResponsibility()
    {
        return $this->currentJobResponsibility;
    }

    /**
     * Set currentJobTitle
     *
     * @param string $currentJobTitle
     * @return Participant
     */
    public function setCurrentJobTitle($currentJobTitle)
    {
        $this->currentJobTitle = $currentJobTitle;

        return $this;
    }

    /**
     * Get currentJobTitle
     *
     * @return string
     */
    public function getCurrentJobTitle()
    {
        return $this->currentJobTitle;
    }

    /**
     * Set qualificationAndEmployement
     *
     * @param string $qualificationAndEmployement
     * @return Participant
     */
    public function setQualificationAndEmployement($qualificationAndEmployement)
    {
        $this->qualificationAndEmployement = $qualificationAndEmployement;

        return $this;
    }

    /**
     * Get qualificationAndEmployement
     *
     * @return string
     */
    public function getQualificationAndEmployement()
    {
        return $this->qualificationAndEmployement;
    }

    /**
     * Set town
     *
     * @param string $town
     * @return Participant
     */
    public function setTown($town)
    {
        $this->town = $town;

        return $this;
    }

    /**
     * Get town
     *
     * @return string
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * Set district
     *
     * @param string $district
     * @return Participant
     */
    public function setDistrict($district)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district
     *
     * @return string
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return Participant
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
     * Set uid
     *
     * @param string $uid
     * @return Participant
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
     * Set username
     *
     * @param string $username
     * @return Participant
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function __toString()
    {
        return $this->firstname." ".$this->middlename." ".$this->lastname;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


    }
}