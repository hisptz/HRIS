<?php

namespace Hris\LeaveBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
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
 * LeaveReport controller.
 *
 * @Route("/leaveReport")
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
            return array(
            'leaveReportForm'=>$leaveReportForm->createView(),
        );
    }

    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="report_leave_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $leaveReportForm = $this->createForm(new LeaveReportType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        $leaveReportForm->bind($request);


        if ($leaveReportForm->isValid()) {
            $leaveReportFormData = $leaveReportForm->getData();
            $organisationUnit = $leaveReportFormData['organisationunit'];
            $forms = $leaveReportFormData['forms'];
            $profession = $leaveReportFormData['fields'];
            $leaveTypes = $leaveReportFormData['leaveTypes'];
            $reportType = $leaveReportFormData['reportType'];
            $withLowerLevels = $leaveReportFormData['withLowerLevels'];
            $chatType = $leaveReportFormData['chatType'];
            $startdate = ($leaveReportFormData['startdate'] == "")?"":$leaveReportFormData['startdate']->format('Y-m-d');
            $enddate = ($leaveReportFormData['enddate'] == "")?"":$leaveReportFormData['enddate']->format('Y-m-d');
        }

        //creating array of selected professionals
        $professionArray = array();
        $professionTitle = "";
        $leaveTitle = "";
        foreach($profession as $prof){
            $professionArray[] = $prof->getValue();
            $professionTitle .= $prof->getValue()." ";
        }

       //creating array of selected leaves
        $leaveArray = array();
        foreach($leaveTypes as $leave){
            $leaveArray[] = $leave->getName();
            $leaveTitle .= $leave->getName()." ";
        }
        //Get the Id for the form
        $formsId = $forms->getId();
        if($reportType == "leaveReport"){
           $results = $this->recordsWithLeave($organisationUnit, $forms, "", $withLowerLevels,$leaveArray,$startdate,$enddate,$professionArray);
            return $this->render(
                'HrisLeaveBundle:LeaveReport:employeeleave.html.twig',
                array(
                'results' => $results,
                'organisationUnit' => $organisationUnit,
                'formsId' => $formsId,
                'forms'   => $forms,
                'reportType' => $reportType,
                'withLowerLevels' => $withLowerLevels,
                'professionTitle' => $professionTitle,
                'profession' => $professionArray,
                'leaveTitle'     => $leaveTitle,
                'leaves'     => $leaveArray,
                'startdate'  => $startdate,
                'enddate'  => $enddate,
            ));
        }
        elseif($reportType == "onLeaveReport"){
            $results = $this->recordsWithLeave($organisationUnit, $forms, "current", $withLowerLevels,$leaveArray,"","",$professionArray);
            return $this->render(
                'HrisLeaveBundle:LeaveReport:employeeOnleave.html.twig',
                array(
                'results' => $results,
                'organisationUnit' => $organisationUnit,
                'formsId' => $formsId,
                'forms'   => $forms,
                'reportType' => $reportType,
                'withLowerLevels' => $withLowerLevels,
                'professionTitle' => $professionTitle,
                    'profession' => $professionArray,
                    'leaveTitle'     => $leaveTitle,
                    'leaves'     => $leaveArray,
                    'startdate'  => $startdate,
                    'enddate'  => $enddate,
            ));
        }
        elseif($reportType == "leaveSummary"){
            $results = $this->leaveSummaryData($organisationUnit, $forms, "", $withLowerLevels,$leaveArray,$startdate,$enddate,$professionArray);
            $yearresults = $this->detailedLeaveYearData($organisationUnit, $forms, "", $withLowerLevels,$leaveArray,$startdate,$enddate,$professionArray);
            $entityManager = $this->getDoctrine()->getManager();
            $leaveName = array();
            $year = array();
            $yearData = array();
            $leaveData = array();
            foreach($results as $result){
                $leaveName[] = $result['data'];
                $leaveData[] = $result['total'];
            }
            foreach($yearresults as $yearresult){
                $year[] = $yearresult['data'];
                $yearData[] = $yearresult['total'];
            }
             $serializer = $this->container->get('serializer');
           return $this->render(
                'HrisLeaveBundle:LeaveReport:leaveSummary.html.twig',
                array(
                    'results' => $results,
                    'organisationUnit' => $organisationUnit,
                    'formsId' => $formsId,
                    'forms'   => $forms,
                    'reportType' => $reportType,
                    'withLowerLevels' => $withLowerLevels,
                    'professionTitle' => $professionTitle,
                    'profession' => $professionArray,
                    'leaveTitle'     => $leaveTitle,
                    'leaves'     => $leaveArray,
                    'chatType'     => $chatType,
                    'leaveName' => $serializer->serialize($leaveName,'json'),
                    'leaveData' => $serializer->serialize($leaveData,'json'),
                    'yearData' => $serializer->serialize($yearData,'json'),
                    'years' => $serializer->serialize($year,'json'),
                    'startdate'  => $startdate,
                    'enddate'  => $enddate,
                ));
        }
    }


    /**
     * Produce year data for specific Leave Type
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param $reportType
     * @param $withLowerLevels
     * @param $leaveType
     * @param $startdate
     * @param $enddate
     * @param $proffesion
     * @return mixed
     */

    private function detailedLeaveYearData(Organisationunit $organisationUnit,  Form $forms, $reportType, $withLowerLevels,$leaveType=array(),$startdate,$enddate,$proffesion=array())
    {
        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        //generating the fields which are concerned with leave only.
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.name FROM hris_leave_type L"
        ) -> fetchAll();
        $leaves = array();
        foreach($leaveTypes as $leave){
            $leaves[] = $leave['name'];
        }

        //checking if date range is selected
        if($startdate != "" && $enddate != ""){
            $datequery = " AND H.startdate between '".$startdate."' and '".$enddate."' "." OR H.enddate between '".$startdate."' and '".$enddate."'";
        }elseif($startdate == "" && $enddate != ""){
            $datequery = " AND H.enddate <= '".$enddate."' ";
        }elseif($startdate != "" && $enddate == ""){
            $datequery = " AND H.startdate >= '".$startdate."' ";
        }else{
            $datequery =" ";
        }
        if($reportType != ""){
            $reportType = " AND '". date('Y-m-d') ."' between H.startdate and H.enddate";
        }
        if(count($proffesion) != 0){
            $reportType .= " AND R.profession IN ('".implode("', '", $proffesion)."') ";
        }else{

        }

        //Query all lower levels units from the passed orgunit
        if($withLowerLevels){
            $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
            $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
            $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
            $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
            $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
        }else{
            $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
        }

        //Query all history data and history year
        $query = "SELECT date_part('year',startdate) as data, count(date_part('year',startdate)) as total ";
        $query .= "FROM hris_record_history H ";
        $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
        $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($leaveType) == 0){
            $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportType;
        }else{
            $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaveType)."') ".$reportType;
        }
        $query .= " AND (". $subQuery .") ".$datequery;
        $query .= " GROUP BY date_part('year',startdate) ";
        $query .= " ORDER BY data ASC";

        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;

    }

    /**
     * return summary of leave data
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param $reportType
     * @param $withLowerLevels
     * @param $leaveType
     * @param $startdate
     * @param $enddate
     * @param $proffesion
     * @return mixed
     */

    private function leaveSummaryData(Organisationunit $organisationUnit,  Form $forms, $reportType, $withLowerLevels,$leaveType=array(),$startdate,$enddate,$proffesion=array()){
        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        //generating the fields which are concerned with leave only.
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.name FROM hris_leave_type L"
        ) -> fetchAll();
        $leaves = array();
        foreach($leaveTypes as $leave){
            $leaves[] = $leave['name'];
        }

        //checking if date range is selected
        if($startdate != "" && $enddate != ""){
            $datequery = " AND H.startdate between '".$startdate."' and '".$enddate."' "." OR H.enddate between '".$startdate."' and '".$enddate."'";
        }elseif($startdate == "" && $enddate != ""){
            $datequery = " AND H.enddate <= '".$enddate."' ";
        }elseif($startdate != "" && $enddate == ""){
            $datequery = " AND H.startdate >= '".$startdate."' ";
        }else{
            $datequery =" ";
        }
        if($reportType != ""){
            $reportType = " AND '". date('Y-m-d') ."' between H.startdate and H.enddate";
        }
        if(count($proffesion) != 0){
            $reportType .= " AND R.profession IN ('".implode("', '", $proffesion)."') ";
        }else{

        }

        //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }


            //Query all history data and count by field option
            $query = "SELECT H.history as data, count (H.history) as total ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($leaveType) == 0){
            $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportType;
        }else{
            $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaveType)."') ".$reportType;
        }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " GROUP BY H.history ";
            $query .= " ORDER BY data ASC";

            $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
            return $report;

    }


    /**
     * return records with leave
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param $reportType
     * @param $withLowerLevels
     * @param $leaveType
     * @param $startdate
     * @param $enddate
     * @param $proffesion
     * @return mixed
     */
    private function recordsWithLeave(Organisationunit $organisationUnit,  Form $forms, $reportType, $withLowerLevels,$leaveType=array(),$startdate,$enddate,$proffesion=array())
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        //generating the fields which are concerned with leave only.
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.name FROM hris_leave_type L"
        ) -> fetchAll();
        $leaves = array();
        foreach($leaveTypes as $leave){
            $leaves[] = $leave['name'];
        }

        //checking if date range is selected
        if($startdate != "" && $enddate != ""){
            $datequery = " AND H.startdate between '".$startdate."' and '".$enddate."' "." OR H.enddate between '".$startdate."' and '".$enddate."'";
        }elseif($startdate == "" && $enddate != ""){
            $datequery = " AND H.enddate <= '".$enddate."' ";
        }elseif($startdate != "" && $enddate == ""){
            $datequery = " AND H.startdate >= '".$startdate."' ";
        }else{
            $datequery =" ";
        }
        if($reportType != ""){
           $reportType = " AND '". date('Y-m-d') ."' between H.startdate and H.enddate";
        }
        if(count($proffesion) != 0){
           $reportType .= " AND R.profession IN ('".implode("', '", $proffesion)."') ";
        }else{

        }


            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            //Query all history data and count by field option
            $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            if(count($leaveType) == 0){
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportType;
            }else{
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaveType)."') ".$reportType;
            }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " ORDER BY R.firstname ASC";
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }

    /**
     * Aggregation Engine
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param $profession
     * @param $leaves
     * @param $withLowerLevels
     * @param $reportType
     * @param $startdate
     * @param $enddate
     * @return mixed
     */
    private function aggregationEngine(Organisationunit $organisationUnit,  Form $forms, $profession=array(), $leaves=array(),  $withLowerLevels,$reportType,$startdate,$enddate)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        //generating the fields which are concerned with leave only.
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.name FROM hris_leave_type L"
        ) -> fetchAll();
        $allLeaves = array();
        foreach($leaveTypes as $leave){
            $allLeaves[] = $leave['name'];
        }

        //checking if date range is selected
        if($startdate != "" && $enddate != ""){
            $datequery = " AND H.startdate between '".$startdate."' and '".$enddate."' "." OR H.enddate between '".$startdate."' and '".$enddate."'";
        }elseif($startdate == "" && $enddate != ""){
            $datequery = " AND H.enddate <= '".$enddate."' ";
        }elseif($startdate != "" && $enddate == ""){
            $datequery = " AND H.startdate >= '".$startdate."' ";
        }else{
            $datequery =" ";
        }
        $reportTe = "";
        if($reportType == "onLeaveReport"){
            $reportTe .= " AND '". date('Y-m-d') ."' between H.startdate and H.enddate";
        }
        if(count($profession) != 0){
            $reportTe .= " AND R.profession IN ('".implode("', '", $profession)."') ";
        }else{

        }

        //summary of employee taking leave
        if($reportType == "leaveReport"){
            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            //Query all history data and count by field option
            $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            if(count($leaves) == 0){
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $allLeaves)."') ".$reportTe;
            }else{
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportTe;
            }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " ORDER BY R.firstname ASC";

        //summary of employee currently having leave
        }elseif($reportType == "onLeaveReport"){
            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            //Query all history data and count by field option
            $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            if(count($leaves) == 0){
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $allLeaves)."') ".$reportTe;
            }else{
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportTe;
            }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " ORDER BY R.firstname ASC";
            //leave summaries
        }elseif($reportType == "leaveSummary"){
            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            //Query all history data and history year
            $query = "SELECT date_part('year',startdate) as data, count(date_part('year',startdate)) as total ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            if(count($leaves) == 0){
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $allLeaves)."') ".$reportTe;
            }else{
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportTe;
            }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " GROUP BY date_part('year',startdate) ";
            $query .= " ORDER BY data ASC";

        }

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }


    /**
     * Download Leave reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOAD")
     * @Route("/download", name="report_leave_download")
     * @Method("GET")
     * @Template()
     */
    public function downloadAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $organisationUnitid =$request->query->get('organisationUnit');
        $formsId = $request->query->get('formsId');
        $reportType = $request->query->get('reportType');
        $withLowerLevels =$request->query->get('withLowerLevels');
        $profession =$request->query->get('profession');
        $leaves = $request->query->get('leaves');
        $startdate = $request->query->get('startdate');
        $enddate = $request->query->get('enddate');


        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);
        $forms = $em->getRepository('HrisFormBundle:Form')->find($formsId);

        $results = $this->aggregationEngine($organisationUnit, $forms, $profession, $leaves,  $withLowerLevels,$reportType,$startdate,$enddate);


        //create the title
        if ($reportType == "onLeaveReport"){
            $subtitle = "Staff On Leave Reports ";
        }
        elseif( $reportType = "leaveSummary"){
            $subtitle = "Leave Summary Report";
        }
        elseif( $reportType = "leaveReport"){
            $subtitle = "Leave Entitlement Report";
        }

        $title = $subtitle. "  ".$organisationUnit->getLongname();

        if($withLowerLevels){
            $title .= " with lower levels";
        }

        // ask the service for a Excel5
        $excelService = $this->get('phpexcel')->createPHPExcelObject();
        $excelService->getProperties()->setCreator("HRHIS3")
            ->setLastModifiedBy("HRHIS3")
            ->setTitle($title)
            ->setSubject("Office 2005 XLSX Test Document")
            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");

        //write the header of the report
        $column = 'A';
        $row  = 1;
        $date = "Date: ".date("jS F Y");
        $excelService->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        $excelService->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);
        $excelService->setActiveSheetIndex(0)
            ->setCellValue($column.$row++, $title)
            ->setCellValue($column.$row, $date);
        //add style to the header
        $heading_format = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => '3333FF'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        //add style to the Value header
        $header_format = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => '000099') ,
            ),
        );
        //add style to the text to display
        $text_format1 = array(
            'font' => array(
                'bold' => false,
                'color' => array('rgb' => '000000'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        //add style to the Value header
        $text_format2 = array(
            'font' => array(
                'bold' => false,
                'color' => array('rgb' => '000000'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => 'E0E0E0') ,
            ),
        );

        $excelService->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        $excelService->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        //reset the colomn and row number
        $column == 'A';
        $row += 2;
        //Start populating excel with data
        if ($reportType == "leaveSummary") {

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:G2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:G1');
            $excelService->getActiveSheet()->mergeCells('A2:G2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:G4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Name')
                ->setCellValue($column++.$row, 'Profession')
                ->setCellValue($column++.$row, 'Leave')
                ->setCellValue($column++.$row, 'Last Leave')
                ->setCellValue($column++.$row, 'Days Spent')
                ->setCellValue($column.$row, 'Duty Post');

            //write the values
            $i =1; //count the row
            foreach($results as $result){
                $column = 'A';//return to the 1st column
                $row++; //increment one row

                //format of the row
                if (($row % 2) == 1)
                    $excelService->getActiveSheet()->getStyle($column.$row.':G'.$row)->applyFromArray($text_format1);
                else
                    $excelService->getActiveSheet()->getStyle($column.$row.':G'.$row)->applyFromArray($text_format2);
                $excelService->setActiveSheetIndex(0)
                    ->setCellValue($column++.$row, $i++)
                    ->setCellValue($column++.$row, $result['firstname']." ".$result['middlename']." ".$result['surname'])
                    ->setCellValue($column++.$row, $result['profession'])
                    ->setCellValue($column++.$row, $result['history'])
                    ->setCellValue($column++.$row, $this->LastLeaveDay1($result['record_id'],$result['history']))
                    ->setCellValue($column++.$row, $this->leaveDaysCaluculator1($result['record_id'],$result['history']))
                    ->setCellValue($column.$row, $result['level5_facility']);
            }

            }
        elseif ($reportType == "onLeaveReport") {

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:G2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:G1');
            $excelService->getActiveSheet()->mergeCells('A2:G2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:G4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Name')
                ->setCellValue($column++.$row, 'Profession')
                ->setCellValue($column++.$row, 'Leave')
                ->setCellValue($column++.$row, 'Start Date')
                ->setCellValue($column++.$row, 'End Date')
                ->setCellValue($column.$row, 'Duty Post');

            //write the values
            $i =1; //count the row
            foreach($results as $result){
                $column = 'A';//return to the 1st column
                $row++; //increment one row

                //format of the row
                if (($row % 2) == 1)
                    $excelService->getActiveSheet()->getStyle($column.$row.':G'.$row)->applyFromArray($text_format1);
                else
                    $excelService->getActiveSheet()->getStyle($column.$row.':G'.$row)->applyFromArray($text_format2);
                $excelService->setActiveSheetIndex(0)
                    ->setCellValue($column++.$row, $i++)
                    ->setCellValue($column++.$row, $result['firstname']." ".$result['middlename']." ".$result['surname'])
                    ->setCellValue($column++.$row, $result['profession'])
                    ->setCellValue($column++.$row, $result['history'])
                    ->setCellValue($column++.$row, $result['startdate'])
                    ->setCellValue($column++.$row, $result['enddate'])
                    ->setCellValue($column.$row, $result['level5_facility']);
            }

            }
        elseif ($reportType == "leaveSummary" ){
            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:C2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:C1');
            $excelService->getActiveSheet()->mergeCells('A2:C2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:C4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Year')
                ->setCellValue($column.$row, 'Value');

            //write the values
            $i =1; //count the row
            foreach($results as $result){
                $column = 'A';//return to the 1st column
                $row++; //increment one row

                //format of the row
                if (($row % 2) == 1)
                    $excelService->getActiveSheet()->getStyle($column.$row.':C'.$row)->applyFromArray($text_format1);
                else
                    $excelService->getActiveSheet()->getStyle($column.$row.':C'.$row)->applyFromArray($text_format2);
                $excelService->setActiveSheetIndex(0)
                    ->setCellValue($column++.$row, $i++)
                    ->setCellValue($column++.$row, $result['data'])
                    ->setCellValue($column.$row, $result['total']);

            }
        }

        $excelService->getActiveSheet()->setTitle('Leave Report');


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $excelService->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($excelService, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename='.str_replace(" ","_",$title).'.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        //$response->sendHeaders();
        return $response;

    }

    /**
     * Aggregation Engine
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param $profession
     * @param $leaves
     * @param $withLowerLevels
     * @param $reportType
     * @param $startdate
     * @param $enddate
     * @return mixed
     */
    private function recordsEngine(Organisationunit $organisationUnit,  Form $forms, $profession=array(), $leaves=array(),  $withLowerLevels,$reportType,$startdate,$enddate)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        //generating the fields which are concerned with leave only.
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.name FROM hris_leave_type L"
        ) -> fetchAll();
        $allLeaves = array();
        foreach($leaveTypes as $leave){
            $allLeaves[] = $leave['name'];
        }

        //checking if date range is selected
        if($startdate != "" && $enddate != ""){
            $datequery = " AND H.startdate between '".$startdate."' and '".$enddate."' "." OR H.enddate between '".$startdate."' and '".$enddate."'";
        }elseif($startdate == "" && $enddate != ""){
            $datequery = " AND H.enddate <= '".$enddate."' ";
        }elseif($startdate != "" && $enddate == ""){
            $datequery = " AND H.startdate >= '".$startdate."' ";
        }else{
            $datequery =" ";
        }
        $reportTe = "";
        if($reportType == "onLeaveReport"){
            $reportTe .= " AND '". date('Y-m-d') ."' between H.startdate and H.enddate";
        }
        if(count($profession) != 0){
            $reportTe .= " AND R.profession IN ('".implode("', '", $profession)."') ";
        }else{

        }

        //summary of employee taking leave
            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            //Query all history data and count by field option
            $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
            $query .= "FROM hris_record_history H ";
            $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            if(count($leaves) == 0){
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $allLeaves)."') ".$reportTe;
            }else{
                $query .= " WHERE V.form_id = ". $forms->getId()." AND H.history IN ('".implode("', '", $leaves)."') ".$reportTe;
            }
            $query .= " AND (". $subQuery .") ".$datequery;
            $query .= " ORDER BY R.firstname ASC";

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }

    /**
     * Download history reports by Cadre
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOADBYCADRE")
     * @Route("/records", name="report_leave_download_records")
     * @Method("GET")
     * @Template()
     */
    public function recordsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $organisationUnitid =$request->query->get('organisationUnit');
        $formsId = $request->query->get('formsId');
        $reportType = $request->query->get('reportType');
        $withLowerLevels =$request->query->get('withLowerLevels');
        $profession =$request->query->get('profession');
        $leaves = $request->query->get('leaves');
        $startdate = $request->query->get('startdate');
        $enddate = $request->query->get('enddate');

        //Get the objects from the the variables

        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);
        $forms = $em->getRepository('HrisFormBundle:Form')->find($formsId);

        $results = $this->recordsEngine($organisationUnit, $forms, $profession, $leaves, $withLowerLevels,$reportType,$startdate,$enddate);

        //create the title
        if ($reportType == "onLeaveReport"){
            $subtitle = "Staff On Leave Reports ";
        }
        elseif( $reportType = "leaveSummary"){
            $subtitle = "Leave Summary Report";
        }
        elseif( $reportType = "leaveReport"){
            $subtitle = "Leave Entitlement Report";
        }

        $title = $subtitle. "  ".$organisationUnit->getLongname();

        if($withLowerLevels){
            $title .= " with lower levels";
        }


        // ask the service for a Excel5
        $excelService = $this->get('phpexcel')->createPHPExcelObject();
        $excelService->getProperties()->setCreator("HRHIS3")
            ->setLastModifiedBy("HRHIS3")
            ->setTitle($title)
            ->setSubject("Office 2005 XLSX Test Document")
            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");

        //write the header of the report
        $column = 'A';
        $row  = 1;
        $date = "Date: ".date("jS F Y");
        $excelService->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        $excelService->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
        $excelService->setActiveSheetIndex(0)
            ->setCellValue($column.$row++, $title)
            ->setCellValue($column.$row, $date);
        //add style to the header
        $heading_format = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => '3333FF'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        //add style to the Value header
        $header_format = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => '000099') ,
            ),
        );
        //add style to the text to display
        $text_format1 = array(
            'font' => array(
                'bold' => false,
                'color' => array('rgb' => '000000'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ),
        );
        //add style to the Value header
        $text_format2 = array(
            'font' => array(
                'bold' => false,
                'color' => array('rgb' => '000000'),
            ),
            'alignment' => array(
                'wrap'       => true,
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => 'E0E0E0') ,
            ),
        );

        $excelService->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        $excelService->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        //reset the colomn and row number
        $column == 'A';
        $row += 2;


            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:H2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:H1');
            $excelService->getActiveSheet()->mergeCells('A2:H2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:H4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Name')
                ->setCellValue($column++.$row, 'Profession')
                ->setCellValue($column++.$row, 'Leave')
                ->setCellValue($column++.$row, 'Start Date')
                ->setCellValue($column++.$row, 'End Date')
                ->setCellValue($column++.$row, 'Leave Benefit')
                ->setCellValue($column.$row, 'Duty Post');

            //write the values
            $i =1; //count the row
            foreach($results as $result){
                $column = 'A';//return to the 1st column
                $row++; //increment one row

                //format of the row
                if (($row % 2) == 1)
                    $excelService->getActiveSheet()->getStyle($column.$row.':I'.$row)->applyFromArray($text_format1);
                else
                    $excelService->getActiveSheet()->getStyle($column.$row.':I'.$row)->applyFromArray($text_format2);
                $excelService->setActiveSheetIndex(0)
                    ->setCellValue($column++.$row, $i++)
                    ->setCellValue($column++.$row, $result['firstname']." ".$result['middlename']." ".$result['surname'])
                    ->setCellValue($column++.$row, $result['profession'])
                    ->setCellValue($column++.$row, $result['history'])
                    ->setCellValue($column++.$row, $result['startdate'])
                    ->setCellValue($column++.$row, $result['enddate'])
                    ->setCellValue($column++.$row, $result['entitled_payment'])
                    ->setCellValue($column.$row, $result['level5_facility']);


        }

        $excelService->getActiveSheet()->setTitle('List of Records');


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $excelService->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($excelService, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename='.str_replace(" ","_",$title).'.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        //$response->sendHeaders();
        return $response;
    }

    /**
     * Returns Fields json.
     *
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/reportFormFields.{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="report_formfields")
     * @Method("POST")
     * @Template()
     */
    public function reportFieldsAction($_format)
    {
        $em = $this->getDoctrine()->getManager();
        $formid = $this->getRequest()->request->get('formid');
        //$formid = 13;

        // Fetch existing feidls belonging to selected form
        $form = $em->getRepository('HrisFormBundle:Form')->findOneBy(array('id'=>$formid));
        $formFields = new ArrayCollection();
        foreach($form->getFormFieldMember() as $formFieldMemberKey=>$formFieldMember) {
            $formFields->add($formFieldMember->getField());
        }

        foreach($formFields as $formFieldsKey=>$formField) {
            if($formField->getHashistory() && $formField->getInputType()->getName() == "Select"){
                $fieldNodes[] = Array(
                    'name' => $formField->getCaption(),
                    'id' => $formField->getId()
                );
            }
        }

        $serializer = $this->container->get('serializer');

        return array(
            'entities' => $serializer->serialize($fieldNodes,$_format)
        );
    }

    /**
     * Returns Caendar Event.
     *
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/calender", name="report_calendar")
     * @Method("GET")
     * @Template()
     */
    public function calendarAction()
    {
        return $this->render('HrisLeaveBundle:LeaveReport:calendar.html.twig', array(
                // ...
            ));    }

    /**
     * caluculate the days required for leave
     *
     */
    public function leaveDaysCaluculatorAction($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option

        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' AND date_part('year',startdate) = '".date("Y")."' ";


        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
         $diff = 0;
        foreach($report as $dates){
            $date1 = new \DateTime(date('Y-m-d',strtotime($dates['startdate'])));
            $date2 = new \DateTime(date('Y-m-d',strtotime($dates['enddate'])));

            $diff += $date2->diff($date1)->format("%a");
        }
        return new Response($diff);
    }

    /**
     * caluculate the days required for leave
     *
     */
    public function leaveDaysCaluculator1($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option

        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' AND date_part('year',startdate) = '".date("Y")."' ";


        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
         $diff = 0;
        foreach($report as $dates){
            $date1 = new \DateTime(date('Y-m-d',strtotime($dates['startdate'])));
            $date2 = new \DateTime(date('Y-m-d',strtotime($dates['enddate'])));

            $diff += $date2->diff($date1)->format("%a");
        }
        return $diff;
    }

    /**
     * caluculate the remaining days for leave
     *
     */
    public function remainingleaveDaysAction($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.maximum_days FROM hris_leave_type L WHERE L.name='".$leave."'"
        ) -> fetchAll();
        $leavedays = array();
        foreach($leaveTypes as $days){
            $leavedays[] = $days['maximum_days'];
        }
        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' AND date_part('year',startdate) = '".date("Y")."' ";

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        $diff = 0;
        foreach($report as $dates){
            $date1 = new \DateTime(date('Y-m-d',strtotime($dates['startdate'])));
            $date2 = new \DateTime(date('Y-m-d',strtotime($dates['enddate'])));

            $diff += $date2->diff($date1)->format("%a");
        }
        if($leavedays[0] == ''){
            $limit = "no limit";
        }else{
            $limit = $leavedays[0] - $diff;
            if($limit > 365){
                $limit = 365;
            }
        }
        return new Response($limit);
    }

     /**
     * caluculate the remaining days for leave to use within excel report
     *
     */
    public function remainingleaveDays1($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option
        $leaveTypes = $entityManager -> getConnection() -> executeQuery(
            "SELECT L.maximum_days FROM hris_leave_type L WHERE L.name='".$leave."'"
        ) -> fetchAll();
        $leavedays = array();
        foreach($leaveTypes as $days){
            $leavedays[] = $days['maximum_days'];
        }
        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' AND date_part('year',startdate) = '".date("Y")."' ";

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        $diff = 0;
        foreach($report as $dates){
            $date1 = new \DateTime(date('Y-m-d',strtotime($dates['startdate'])));
            $date2 = new \DateTime(date('Y-m-d',strtotime($dates['enddate'])));

            $diff += $date2->diff($date1)->format("%a");
        }
        if($leavedays[0] == ''){
            $limit = "no limit";
        }else{
            $limit = $leavedays[0] - $diff;
            if($limit > 365){
                $limit = 365;
            }
        }
        return $limit;
    }

    /**
     * caluculate the day for the last leave
     *
     */
    public function LastLeaveDayAction($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option

        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' ";
        $query .= " ORDER BY H.startdate DESC LIMIT 1";

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();

        foreach($report as $dates){
           $lastleavedate = date('j, M Y',strtotime($dates['startdate']));
        }
        return new Response($lastleavedate);
    }

        /**
     * caluculate the day for the last leave to use within excel reports
     *
     */
    public function LastLeaveDay1($recordid,$leave){
        $entityManager = $this->getDoctrine()->getManager();
        //Query all history data and count by field option

        $query = "SELECT H.startdate, H.enddate ";
        $query .= "FROM hris_record_history H ";
        $query .= " WHERE H.record_id = ". $recordid." AND H.history = '".$leave."' ";
        $query .= " ORDER BY H.startdate DESC LIMIT 1";

        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();

        foreach($report as $dates){
           $lastleavedate = date('j, M Y',strtotime($dates['startdate']));
        }
        return $lastleavedate;
    }

    /**
     * caluculate the days for the last leave
     * @param $startdate
     * @param $enddate
     * @return mixed
     */
    public function allDatesArray($startdate,$enddate){
        $begin = new \DateTime( $startdate );
        $end = new \DateTime( $enddate );

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);
        $dateArray = array();
        foreach ( $period as $dt ){
            $dateArray[] = $dt->format( "Y-m-d" );
        }
        return $dateArray;
    }

}

