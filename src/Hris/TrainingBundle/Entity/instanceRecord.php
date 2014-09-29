<?php

namespace Hris\TrainingBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * instanceRecord
 *
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_instance_records")
 * @ORM\Entity
 */
class instanceRecord
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
     * @ORM\Column(name="record_id", type="integer")
     */
    private $recordId;


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
     * @return instanceRecord
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
     * Set recordId
     *
     * @param integer $recordId
     * @return instanceRecord
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;
    
        return $this;
    }

    /**
     * Get recordId
     *
     * @return integer 
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return instanceRecord
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
}