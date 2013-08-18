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
namespace Hris\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Hris\DashboardBundle\Entity\DashboardChart;

/**
 * Hris\UserBundle\Entity\UserInfo
 *
 * @ORM\Table(name="hris_userinfo")
 * @ORM\Entity(repositoryClass="Hris\UserBundle\Entity\UserInfoRepository")
 */
class UserInfo
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
     * @ORM\Column(name="uid", type="string", length=13, nullable=false, unique=true)
     */
    private $uid;

    /**
     * @var string $phonenumber
     *
     * @ORM\Column(name="phonenumber", type="string", length=64)
     */
    private $phonenumber;

    /**
     * @var string $jobTitle
     *
     * @ORM\Column(name="jobTitle", type="string", length=64)
     */
    private $jobTitle;

    /**
     * @var string $firstName
     *
     * @ORM\Column(name="firstName", type="string", length=64)
     */
    private $firstName;

    /**
     * @var string $middleName
     *
     * @ORM\Column(name="middleName", type="string", length=64)
     */
    private $middleName;

    /**
     * @var string $surname
     *
     * @ORM\Column(name="surname", type="string", length=64)
     */
    private $surname;
    
    /**
     * @var DashboardChart $dashboardChart
     *
     * @ORM\OneToMany(targetEntity="Hris\DashboardBundle\Entity\DashboardChart", mappedBy="userInfo",cascade={"ALL"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $dashboardChart;


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
     * Set phonenumber
     *
     * @param string $phonenumber
     * @return UserInfo
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    
        return $this;
    }

    /**
     * Get phonenumber
     *
     * @return string 
     */
    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     * Set jobTitle
     *
     * @param string $jobTitle
     * @return UserInfo
     */
    public function setJobTitle($jobTitle)
    {
        $this->jobTitle = $jobTitle;
    
        return $this;
    }

    /**
     * Get jobTitle
     *
     * @return string 
     */
    public function getJobTitle()
    {
        return $this->jobTitle;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return UserInfo
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    
        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set middleName
     *
     * @param string $middleName
     * @return UserInfo
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
    
        return $this;
    }

    /**
     * Get middleName
     *
     * @return string 
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set surname
     *
     * @param string $surname
     * @return UserInfo
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
    
        return $this;
    }

    /**
     * Get surname
     *
     * @return string 
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dashboardChart = new ArrayCollection();
    }
    
    /**
     * Set uid
     *
     * @param string $uid
     * @return UserInfo
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
     * Add dashboardChart
     *
     * @param DashboardChart $dashboardChart
     * @return UserInfo
     */
    public function addDashboardChart(DashboardChart $dashboardChart)
    {
        $this->dashboardChart[$dashboardChart->getId()] = $dashboardChart;
    
        return $this;
    }

    /**
     * Remove dashboardChart
     *
     * @param DashboardChart $dashboardChart
     */
    public function removeDashboardChart(DashboardChart $dashboardChart)
    {
        $this->dashboardChart->removeElement($dashboardChart);
    }

    /**
     * Get dashboardChart
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDashboardChart()
    {
        return $this->dashboardChart;
    }
}