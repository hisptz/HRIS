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
 * @author Kelvin Mbwilo <kelvinmbwilo@gmail.com>
 *
 */
namespace Hris\LeaveBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hris\FormBundle\Entity\FieldOption;
use Hris\LeaveBundle\Entity\Leave;

use Symfony\Component\Validator\Constraints as Assert;


/**
 * LeaveType
 *
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_leave_type")
 * @ORM\Entity(repositoryClass="Hris\LeaveBundle\Entity\LeaveTypeRepository")
 */
class LeaveType
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
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="uid", type="string", length=25)
     */
    private $uid;

    /**
     * @var integer
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="maximum_days", type="integer", nullable=true)
     */
    private $maximumDays;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="limit_applicable", type="string", length=13, nullable=true)
     */
    private $limitApplicable;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_applicable", type="string", length=13,nullable=true)
     */
    private $paymentApplicable;

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

    // ...

    /**
     * @ORM\OneToOne(targetEntity="Hris\FormBundle\Entity\FieldOption")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $field;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();
        $this->datecreated = new \DateTime('now');
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
     * Set name
     *
     * @param string $name
     * @return LeaveType
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return LeaveType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return LeaveType
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
     * Set maximumDays
     *
     * @param integer $maximumDays
     * @return LeaveType
     */
    public function setMaximumDays($maximumDays)
    {
        $this->maximumDays = $maximumDays;
    
        return $this;
    }

    /**
     * Get maximumDays
     *
     * @return integer 
     */
    public function getMaximumDays()
    {
        return $this->maximumDays;
    }

    /**
     * Set limitApplicable
     *
     * @param string $limitApplicable
     * @return LeaveType
     */
    public function setLimitApplicable($limitApplicable)
    {
        $this->limitApplicable = $limitApplicable;
    
        return $this;
    }

    /**
     * Get limitApplicable
     *
     * @return string 
     */
    public function getLimitApplicable()
    {
        return $this->limitApplicable;
    }

    /**
     * Set paymentApplicable
     *
     * @param string $paymentApplicable
     * @return LeaveType
     */
    public function setPaymentApplicable($paymentApplicable)
    {
        $this->paymentApplicable = $paymentApplicable;
    
        return $this;
    }

    /**
     * Get paymentApplicable
     *
     * @return string 
     */
    public function getPaymentApplicable()
    {
        return $this->paymentApplicable;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return LeaveType
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
     * @return LeaveType
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
     * Set field
     *
     * @param FieldOption $field
     * @return LeaveType
     */
    public function setRecord(FieldOption $field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return FieldOption
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get Entity verbose name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

}