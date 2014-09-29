<?php

namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * instanceTrainer
 *@Gedmo\Loggable
 * @ORM\Table("hris_instanceTrainer")
 * @ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\instanceTrainerRepository")
 */
class instanceTrainer
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
     * @var integer
     *
     * @ORM\Column(name="instance_id", type="integer")
     */
    private $instanceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="trainer_id", type="integer")
     */
    private $trainerId;



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
     * Set instanceId
     *
     * @param integer $instanceId
     * @return instanceTrainer
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
    
        return $this;
    }

    /**
     * Get instanceId
     *
     * @return integer 
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * Set trainerId
     *
     * @param integer $trainerId
     * @return instanceTrainer
     */
    public function setTrainerId($trainerId)
    {
        $this->trainerId = $trainerId;
    
        return $this;
    }

    /**
     * Get trainerId
     *
     * @return integer 
     */
    public function getTrainerId()
    {
        return $this->trainerId;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return instanceTrainer
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
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return instanceTrainer
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
     * @return instanceTrainer
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
}