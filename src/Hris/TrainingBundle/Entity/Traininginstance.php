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
 * @author Leonard C Mpande <leo.august27@gmail.com>
 *
 */



namespace Hris\TrainingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hris\RecordsBundle\Entity;
use Hris\RecordsBundle\Entity\Record;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 *Traininginstance
 *@Gedmo\Loggable
 *@ORM\Table(name="hris_traininginstance")
 *@ORM\Entity(repositoryClass="Hris\TrainingBundle\Entity\TraininginstanceRepository")
 */


class Traininginstance
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
     * @var String $course
     *
     * @ORM\ManyToOne(targetEntity="Hris\TrainingBundle\Entity\Training")
     * @ORM\JoinColumn(name="training_id", referencedColumnName="id", onDelete="CASCADE")
     *
     */
    private $course;

     /**
     * @var string $region
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="region", type="string", length=100, unique=false)
     */
    private $region;

     /**
     * @var string $district
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="district", type="string", length=100, unique=false)
     */
    private $district;

     /**
     * @var string $venue
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="venue", type="string", length=100, unique=false)
     */
    private $venue;

     /**
     * @var string $sponsor
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="sponsor", type="string", length=100, unique=false)
     */
    private $sponsor;


    /**
     * @var \DateTime $startdate
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="startdate", type="datetime", length=255)
     */
    private $startdate;




    /**
     * @var \DateTime $enddate
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="enddate", type="datetime", length=255)
     */
    private $enddate;


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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();


    }


    /**
     * Set startdate
     *
     * @param \DateTime $startdate
     * @return Traininginstance
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get startdate
     *
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set enddate
     *
     * @param \DateTime $enddate
     * @return Traininginstance
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set courseName
     *
     * @param \Hris\TrainingBundle\Entity\Training $course
     * @return Traininginstance
     */
    public function setCourse(\Hris\TrainingBundle\Entity\Training $course = null)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get courseName
     *
     * @return \Hris\TrainingBundle\Entity\Training
     */
    public function getCourse()
    {
        return $this->course;
    }


    /**
     * Set uid
     *
     * @param string $uid
     * @return Traininginstance
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
     * @return Traininginstance
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
     * Set district
     *
     * @param string $district
     * @return Traininginstance
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
     * Set sponsor
     *
     * @param string $sponsor
     * @return Traininginstance
     */
    public function setSponsor($sponsor)
    {
        $this->sponsor = $sponsor;
    
        return $this;
    }

    /**
     * Get sponsor
     *
     * @return string 
     */
    public function getSponsor()
    {
        return $this->sponsor;
    }

    /**
     * Set venue
     *
     * @param string $venue
     * @return Traininginstance
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
    
        return $this;
    }

    /**
     * Get venue
     *
     * @return string 
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return Traininginstance
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
     * @return Traininginstance
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