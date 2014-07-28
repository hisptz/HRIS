<?php

namespace Hris\LeaveBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
use Hris\LeaveBundle\Form\NursingReportType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Hris\FormBundle\Entity\Form;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Entity\FieldOption;
use Hris\ReportsBundle\Form\ReportHistoryTrainingType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\ReportsBundle\Entity\Report;
use Hris\ReportsBundle\Form\ReportType;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Zend\Json\Expr;
use JMS\SecurityExtraBundle\Annotation\Secure;


/**
 * NursingReport controller.
 *
 * @Route("/nursingReport")
 */
class NursingReportController extends Controller

{
    /**
     * Show Leave Report Records Form
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/", name="nursing_report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $leaveReportForm = $this->createForm(new NursingReportType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        return array(
            'nursingReportForm'=>$leaveReportForm->createView(),
        );
    }
}

