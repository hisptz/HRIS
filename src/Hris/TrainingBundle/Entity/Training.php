<?php
/*
 *
 * Copyright 2012 Human Resource Information System
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @since 2012
 * @author John Francis Mukulu <john.f.mukulu@gmail.com>
 *
 */
namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Hris\TrainingBundle\Entity\Training
 *
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_trainings")
 * @ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\TrainingRepository")
 */
class Training
{
    /**
     * @var integer $id
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
     * @var string $coursename
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="coursename", type="string", length=255)
     */
    private $coursename;
 /**
     * @var string $trainingCategory
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="trainingCategory", type="string", length=255)
     */
    private $trainingCategory;

/**
     * @var string $trainingInstruction
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="trainingInstruction", type="string", length=255)
     */
    private $trainingInstruction;




    /**
     * @var String $curiculum
     * @Gedmo\Versioned
     * @ORM\Column(name="curiculum", type="string", length=64)
     */
    private $curiculum;




    /**
     * @var \DateTime $datecreated
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="datecreated", type="datetime", nullable=false)
     */
    private $datecreated;

    /**
     * @var \DateTime $lastupdated
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="lastupdated", type="datetime", nullable=true)
     */
    private $lastupdated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


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
     * @return Training
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
     * Set coursename
     *
     * @param string $coursename
     * @return Training
     */
    public function setCoursename($coursename)
    {
        $this->coursename = $coursename;
    
        return $this;
    }

    /**
     * Get coursename
     *
     * @return string 
     */
    public function getCoursename()
    {
        return $this->coursename;
    }

    /**
     * Set trainingCategory
     *
     * @param string $trainingCategory
     * @return Training
     */
    public function setTrainingCategory($trainingCategory)
    {
        $this->trainingCategory = $trainingCategory;
    
        return $this;
    }

    /**
     * Get trainingCategory
     *
     * @return string 
     */
    public function getTrainingCategory()
    {
        return $this->trainingCategory;
    }

    /**
     * Set trainingInstruction
     *
     * @param string $trainingInstruction
     * @return Training
     */
    public function setTrainingInstruction($trainingInstruction)
    {
        $this->trainingInstruction = $trainingInstruction;
    
        return $this;
    }

    /**
     * Get trainingInstruction
     *
     * @return string 
     */
    public function getTrainingInstruction()
    {
        return $this->trainingInstruction;
    }


    /**
     * Set curiculum
     *
     * @param string $curiculum
     * @return Training
     */
    public function setCuriculum($curiculum)
    {
        $this->curiculum = $curiculum;
    
        return $this;
    }

    /**
     * Get curiculum
     *
     * @return string 
     */
    public function getCuriculum()
    {
        return $this->curiculum;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return Training
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
     * @return Training
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
        return $this->coursename;
    }


}