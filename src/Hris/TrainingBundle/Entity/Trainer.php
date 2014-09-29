<?php

namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Trainer
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_trainers")
 * @ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\TrainerRepository")
 */
class Trainer
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $uid
     * @Gedmo\Versioned
     * @ORM\Column(name="uid", type="string", length=13, unique=false)
     */
    private $uid;

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
     * @var String $primaryJobResponsibility
     * @Gedmo\Versioned
     * @ORM\Column(name="primaryJobResponsibility", type="string")
     */
    private $primaryJobResponsibility;

/**
     * @var String $secondaryJobResponsibility
     * @Gedmo\Versioned
     * @ORM\Column(name="secondaryJobResponsibility", type="string")
     */
    private $secondaryJobResponsibility;

    /**
     * @var String $profession
     * @Gedmo\Versioned
     * @ORM\Column(name="profession", type="string")
     */
    private $profession;

    /**
     * @var String $currentJobTitle
     * @Gedmo\Versioned
     * @ORM\Column(name="currentJobTitle", type="string")
     */
    private $employer;

/**
     * @var String $placeOfWork
     * @Gedmo\Versioned
     * @ORM\Column(name="placeOfWork", type="string")
     */
    private $placeOfWork;

/**
     * @var String $organisationType
     * @Gedmo\Versioned
     * @ORM\Column(name="organisationType", type="string")
     */
    private $organisationType;

/**
     * @var String $trainerType
     * @Gedmo\Versioned
     * @ORM\Column(name="trainerType", type="string")
     */
    private $trainerType;

/**
     * @var String $trainerLanguage
     * @Gedmo\Versioned
     * @ORM\Column(name="trainerLanguage", type="string")
     */
    private $trainerLanguage;

/**
     * @var String $trainerAffiliation
     * @Gedmo\Versioned
     * @ORM\Column(name="trainerAffiliation", type="string")
     */
    private $trainerAffiliation;

/**
     * @var String $experience
     * @Gedmo\Versioned
     * @ORM\Column(name="experience", type="string")
     */
    private $experience;

/**
     * @var String $highestLevelOfQualification
     * @Gedmo\Versioned
     * @ORM\Column(name="highestLevelOfQualification", type="string")
     */
    private $highestLevelOfQualification;



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




    public function __toString()
    {
        return $this->firstname." ".$this->middlename." ".$this->lastname;
    }

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
     * @return Trainer
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
     * Set firstname
     *
     * @param string $firstname
     * @return Trainer
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
     * @return Trainer
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
     * @return Trainer
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
     * Set primaryJobResponsibility
     *
     * @param string $primaryJobResponsibility
     * @return Trainer
     */
    public function setPrimaryJobResponsibility($primaryJobResponsibility)
    {
        $this->primaryJobResponsibility = $primaryJobResponsibility;
    
        return $this;
    }

    /**
     * Get primaryJobResponsibility
     *
     * @return string 
     */
    public function getPrimaryJobResponsibility()
    {
        return $this->primaryJobResponsibility;
    }

    /**
     * Set secondaryJobResponsibility
     *
     * @param string $secondaryJobResponsibility
     * @return Trainer
     */
    public function setSecondaryJobResponsibility($secondaryJobResponsibility)
    {
        $this->secondaryJobResponsibility = $secondaryJobResponsibility;
    
        return $this;
    }

    /**
     * Get secondaryJobResponsibility
     *
     * @return string 
     */
    public function getSecondaryJobResponsibility()
    {
        return $this->secondaryJobResponsibility;
    }

    /**
     * Set profession
     *
     * @param string $profession
     * @return Trainer
     */
    public function setProfession($profession)
    {
        $this->profession = $profession;
    
        return $this;
    }

    /**
     * Get profession
     *
     * @return string 
     */
    public function getProfession()
    {
        return $this->profession;
    }

    /**
     * Set employer
     *
     * @param string $employer
     * @return Trainer
     */
    public function setEmployer($employer)
    {
        $this->employer = $employer;
    
        return $this;
    }

    /**
     * Get employer
     *
     * @return string 
     */
    public function  getEmployer()
    {
        return $this->employer;
    }

    /**
     * Set placeOfWork
     *
     * @param string $placeOfWork
     * @return Trainer
     */
    public function setPlaceOfWork($placeOfWork)
    {
        $this->placeOfWork = $placeOfWork;
    
        return $this;
    }

    /**
     * Get placeOfWork
     *
     * @return string 
     */
    public function getPlaceOfWork()
    {
        return $this->placeOfWork;
    }

    /**
     * Set organisationType
     *
     * @param string $organisationType
     * @return Trainer
     */
    public function setOrganisationType($organisationType)
    {
        $this->organisationType = $organisationType;
    
        return $this;
    }

    /**
     * Get organisationType
     *
     * @return string 
     */
    public function getOrganisationType()
    {
        return $this->organisationType;
    }

    /**
     * Set trainerType
     *
     * @param string $trainerType
     * @return Trainer
     */
    public function setTrainerType($trainerType)
    {
        $this->trainerType = $trainerType;
    
        return $this;
    }

    /**
     * Get trainerType
     *
     * @return string 
     */
    public function getTrainerType()
    {
        return $this->trainerType;
    }

    /**
     * Set trainerLanguage
     *
     * @param string $trainerLanguage
     * @return Trainer
     */
    public function setTrainerLanguage($trainerLanguage)
    {
        $this->trainerLanguage = $trainerLanguage;
    
        return $this;
    }

    /**
     * Get trainerLanguage
     *
     * @return string 
     */
    public function getTrainerLanguage()
    {
        return $this->trainerLanguage;
    }

    /**
     * Set trainerAffiliation
     *
     * @param string $trainerAffiliation
     * @return Trainer
     */
    public function setTrainerAffiliation($trainerAffiliation)
    {
        $this->trainerAffiliation = $trainerAffiliation;
    
        return $this;
    }

    /**
     * Get trainerAffiliation
     *
     * @return string 
     */
    public function getTrainerAffiliation()
    {
        return $this->trainerAffiliation;
    }

    /**
     * Set experience
     *
     * @param string $experience
     * @return Trainer
     */
    public function setExperience($experience)
    {
        $this->experience = $experience;
    
        return $this;
    }

    /**
     * Get experience
     *
     * @return string 
     */
    public function getExperience()
    {
        return $this->experience;
    }

    /**
     * Set highestLevelOfQualification
     *
     * @param string $highestLevelOfQualification
     * @return string
     */
    public function setHighestLevelOfQualification($highestLevelOfQualification)
    {
        $this->highestLevelOfQualification = $highestLevelOfQualification;
    
        return $this;
    }

    /**
     * Get highestLevelOfQualification
     *
     * @return string 
     */
    public function getHighestLevelOfQualification()
    {
        return $this->highestLevelOfQualification;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return Trainer
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
     * @return Trainer
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


    }
}