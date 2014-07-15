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
use Hris\LeaveBundle\Entity\Leave;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * LeaveRelative
 *
 * @Gedmo\Loggable
 * @ORM\Table(name="hris_leave_relative")
 * @ORM\Entity(repositoryClass="Hris\LeaveBundle\Entity\LeaveRelativeRepository")
 */
class LeaveRelative
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
     * @var \DateTime
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="date_of_birth", type="date")
     */
    private $dateOfBirth;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="age", type="string", length=255)
     */
    private $age;

    /**
     * @var string
     *
     * @Gedmo\Versioned
     * @ORM\Column(name="uid", type="string", length=13)
     */
    private $uid;

    /**
     * @var Leave $leave
     *
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Hris\LeaveBundle\Entity\Leave",inversedBy="leave_relative")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="leave_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */

    private $leave;

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
     * @return LeaveRelative
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
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     * @return LeaveRelative
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    
        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime 
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set age
     *
     * @param string $age
     * @return LeaveRelative
     */
    public function setAge($age)
    {
        $this->age = $age;
    
        return $this;
    }

    /**
     * Get age
     *
     * @return string 
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return LeaveRelative
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
     * Set leave
     *
     * @param Leave $leave
     * @return LeaveRelative
     */
    public function setLeaveType(Leave $leave = null)
    {
        $this->leave = $leave;

        return $this;
    }

    /**
     * Get leave
     *
     * @return Leave
     */
    public function getLeave()
    {
        return $this->leave;
    }



    /**
     * Set leave
     *
     * @param \Hris\LeaveBundle\Entity\Leave $leave
     * @return LeaveRelative
     */
    public function setLeave(\Hris\LeaveBundle\Entity\Leave $leave = null)
    {
        $this->leave = $leave;
    
        return $this;
    }
}