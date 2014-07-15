<?php

namespace Hris\LeaveBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Hris\FormBundle\Entity\Form;
use Hris\FormBundle\Entity\Field;
use Hris\ReportsBundle\Form\ReportHistoryTrainingType;
use Symfony\Component\HttpFoundation\Request;
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
 * LeaveReport controller.
 *
 * @Route("/leave_report")
 */
class LeaveReportController extends Controller
{
    /**
     * Show Leave Report Records Form
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/", name="leave_report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $leaveReportForm = $this->createForm(new LeaveReportType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
//
        return array(
            'leaveReportForm'=>$leaveReportForm->createView(),
        );
    }

    public function calendarAction()
    {
        return $this->render('HrisLeaveBundle:LeaveReport:calendar.html.twig', array(
                // ...
            ));    }

}
