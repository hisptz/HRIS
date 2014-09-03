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
use Hris\RecordsBundle\Entity\Record;
use Hris\LeaveBundle\Entity\LeaveType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Leave
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_leave",uniqueConstraints={ @ORM\UniqueConstraint(name="unique_leave_idx",columns={"record_id", "leave_type_id","startdate"}) })
 * @ORM\Entity(repositoryClass="Hris\LeaveBundle\Entity\LeaveRepository")
 */
class Leave
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
     * @ORM\Column(name="uid", type="string", length=13)
     */
    private $uid;

    /**
     * @var Record $record
     *
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Hris\RecordsBundle\Entity\Record",inversedBy="leave")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="record_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */

    private $record;

    /**
     * @var LeaveType $leave_type
     *
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Hris\LeaveBundle\Entity\LeaveType",inversedBy="leave")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="leave_type_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */

    private $leave_type;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255)
     */
    private $username;

    /**
     * @var LeaveRelative $leave_relative
     *
     * @ORM\OneToMany(targetEntity="Hris\LeaveBundle\Entity\LeaveRelative", mappedBy="leave",cascade={"ALL"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $leave_relative;


    /**
     * @var \DateTime
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="startdate", type="datetime")
     */
    private $startdate;

    /**
     * @var \DateTime
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="enddate", type="datetime")
     */
    private $enddate;

    /**
     * @var integer
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration;

    /**
     * @var integer
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="leave_benefit_applicable", type="string", length=13)
     */
    private $leaveBenefitApplicable;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="leave_benefit_status", type="string", length=13)
     */
    private $leaveBenefitStatus;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="phone", type="string", length=255)
     */
    private $phone;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="leave_destination", type="string", length=255)
     */
    private $leaveDestination;

    /**
     * @var string $reason
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="reason", type="string", length=255, nullable=true)
     */
    private $reason;

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
     * Constructor
     */
    public function __construct()
    {
        $this->uid = uniqid();
        $this->datecreated = new \DateTime('now');
    }

    /**
     * Set record
     *
     * @param Record $record
     * @return Leave
     */
    public function setRecord(Record $record = null)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Get record
     *
     * @return Record
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Set leave_type
     *
     * @param LeaveType $leave_type
     * @return Leave
     */
    public function setLeaveType(LeaveType $leave_type = null)
    {
        $this->leave_type = $leave_type;

        return $this;
    }

    /**
     * Get leave_type
     *
     * @return LeaveType
     */
    public function getLeaveType()
    {
        return $this->leave_type;
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
     * @return Leave
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
     * @return Leave
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

    /**
     * Set startdate
     *
     * @param \DateTime $startdate
     * @return Leave
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
     * @return Leave
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
     * Set duration
     *
     * @param integer $duration
     * @return Leave
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return Leave
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set leaveBenefitApplicable
     *
     * @param string $leaveBenefitApplicable
     * @return Leave
     */
    public function setLeaveBenefitApplicable($leaveBenefitApplicable)
    {
        $this->leaveBenefitApplicable = $leaveBenefitApplicable;

        return $this;
    }

    /**
     * Get leaveBenefitApplicable
     *
     * @return string
     */
    public function getLeaveBenefitApplicable()
    {
        return $this->leaveBenefitApplicable;
    }

    /**
     * Set leaveBenefitStatus
     *
     * @param string $leaveBenefitStatus
     * @return Leave
     */
    public function setLeaveBenefitStatus($leaveBenefitStatus)
    {
        $this->leaveBenefitStatus = $leaveBenefitStatus;

        return $this;
    }

    /**
     * Get leaveBenefitStatus
     *
     * @return string
     */
    public function getLeaveBenefitStatus()
    {
        return $this->leaveBenefitStatus;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Leave
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
     * Set address
     *
     * @param string $address
     * @return Leave
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Leave
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
     * Set leaveDestination
     *
     * @param string $leaveDestination
     * @return Leave
     */
    public function setLeaveDestination($leaveDestination)
    {
        $this->leaveDestination = $leaveDestination;

        return $this;
    }

    /**
     * Get leaveDestination
     *
     * @return string
     */
    public function getLeaveDestination()
    {
        return $this->leaveDestination;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return History
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set datecreated
     *
     * @param \DateTime $datecreated
     * @return History
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
     * @return History
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
     * Get leave_relative
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeaveRelative()
    {
        return $this->leave_relative;
    }


    /**
     * Add leave_relative
     *
     * @param \Hris\LeaveBundle\Entity\LeaveRelative $leaveRelative
     * @return Leave
     */
    public function addLeaveRelative(\Hris\LeaveBundle\Entity\LeaveRelative $leaveRelative)
    {
        $this->leave_relative[] = $leaveRelative;
    
        return $this;
    }

    /**
     * Remove leave_relative
     *
     * @param \Hris\LeaveBundle\Entity\LeaveRelative $leaveRelative
     */
    public function removeLeaveRelative(\Hris\LeaveBundle\Entity\LeaveRelative $leaveRelative)
    {
        $this->leave_relative->removeElement($leaveRelative);
    }
}