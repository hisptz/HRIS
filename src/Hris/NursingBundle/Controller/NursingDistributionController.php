<?php
namespace Hris\NursingBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
use Hris\NursingBundle\Form\NursingDistributionType;
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
 * @Route("/nursesDistributionReport")
 */
class NursingDistributionController extends Controller
{
    /**
     * Show Leave Report Records Form
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/", name="nursing_distribution_report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $distributionReportForm = $this->createForm(new NursingDistributionType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        return array(
            'departmentReportForm'=>$distributionReportForm->createView(),
        );
    }
    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="distribution_nurses_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $serializer = $this->container->get('serializer');
        $distributionReportForm = $this->createForm(new NursingDistributionType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        $distributionReportForm->bind($request);


        if ($distributionReportForm->isValid()) {
            $nursingReportFormData = $distributionReportForm->getData();
            $organisationUnit = $nursingReportFormData['distributionLevel'];
            $region = $nursingReportFormData['region'];
            $forms = $nursingReportFormData['forms'];
            $licence = $nursingReportFormData['NursesLicencing'];
            $chatType = $nursingReportFormData['chatType'];
            $NursingCadre = $nursingReportFormData['NursingCadre'];
        }

        //creating array of selected professionals
        $formsArray = array();
        foreach($forms as $form){
            $formsArray[] = $form->getId();
        }

        $regionArray = array();
        foreach($region as $prof){
            $regionArray[] = $prof;
        }

        //craeting a title
        $licensetitle ="";
        if($licence == "Licensed"){
            $licensetitle = " Licensed ";
        }elseif($licence == "NotLicensed"){
            $licensetitle = "Not Licenced";
        }else{

        }
        if($NursingCadre != ""){
            $title = $NursingCadre." Nurses ";
        }else{
            $title = "Nurses";
        }

        //Get the Id for the form
//        $formsId = $forms->getId();
        $results = $this->nursingRecords($regionArray, $formsArray,$NursingCadre,$licence,$organisationUnit);
        $serializer = $this->container->get('serializer');
        $departmentName = array();
        $departmentData = array();
        foreach($results as $result){
            $departmentName[] = $result['data'];
            $departmentData[] = $result['total'];
        }
        if(count($departmentData) > 25){
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $forms,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize(array_slice($departmentName,0,25),'json'),
                    'departmentData' => $serializer->serialize(array_slice($departmentData,0,25),'json'),
                    'departmentName1' => $serializer->serialize(array_slice($departmentName,24,25),'json'),
                    'departmentData1' => $serializer->serialize(array_slice($departmentData,24,25),'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                ));
        }elseif(count($departmentData) > 50){
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $forms,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize(array_slice($departmentName,0,25),'json'),
                    'departmentData' => $serializer->serialize(array_slice($departmentData,0,25),'json'),
                    'departmentName1' => $serializer->serialize(array_slice($departmentName,24,25),'json'),
                    'departmentData1' => $serializer->serialize(array_slice($departmentData,24,25),'json'),
                    'departmentName2' => $serializer->serialize(array_slice($departmentName,49,25),'json'),
                    'departmentData2' => $serializer->serialize(array_slice($departmentData,49,25),'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                ));
        }elseif(count($departmentData) > 75){
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $forms,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize(array_slice($departmentName,0,25),'json'),
                    'departmentData' => $serializer->serialize(array_slice($departmentData,0,25),'json'),
                    'departmentName1' => $serializer->serialize(array_slice($departmentName,24,25),'json'),
                    'departmentData1' => $serializer->serialize(array_slice($departmentData,24,25),'json'),
                    'departmentName2' => $serializer->serialize(array_slice($departmentName,49,25),'json'),
                    'departmentData2' => $serializer->serialize(array_slice($departmentData,49,25),'json'),
                    'departmentName3' => $serializer->serialize(array_slice($departmentName,74,25),'json'),
                    'departmentData3' => $serializer->serialize(array_slice($departmentData,74,25),'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                ));
        }else{
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $forms,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize($departmentName,'json'),
                    'departmentData' => $serializer->serialize($departmentData,'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                ));
        }


    }

    /**
     * return nursing records
     *
     * @param $organisationUnit
     * @param $regionArray
     * @param $formArray
     * @param $NursingCadre
     * @param $licence
     * @return mixed
     */
    private function nursingRecords($regionArray, $formArray=array(),$NursingCadre,$licence,$organisationUnit)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";
        //checking if date range is selected

        if($NursingCadre == "Enrolled"){
            $cardequery = " AND R.edu_evel = 'Certificate' ";
        }elseif($NursingCadre == "Registered"){
            $cardequery = " AND R.edu_evel != 'Certificate' ";
        }else{
            $cardequery = "";
        }
        if($licence == "Licensed"){
            $licensequery = " AND R.reg_no != '' ";
        }elseif($licence == "NotLicensed"){
            $licensequery = " AND R.reg_no = '' ";
        }else{
            $licensequery = " ";
        }
        if(count($regionArray) == 0){
            $regionQuery = "";
        }else{
            $regionQuery = " AND R.level3_regions_departments_institutions_referrals IN ('".implode("', '", $regionArray)."') ";
        }
        //Query all history data and count by field option
        if($organisationUnit == 'Region'){
            $query = "SELECT level3_regions_departments_institutions_referrals as data, count(level3_regions_departments_institutions_referrals) as total ";
        }else{
            $query = "SELECT level4_districts_reg_hospitals as data, count(level3_regions_departments_institutions_referrals) as total ";
        }
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse'  AND R.level3_regions_departments_institutions_referrals like '%Region' ".$cardequery.$licensequery.$regionQuery;
        }else{
            $query .= " WHERE R.profession = 'Nurse' AND R.level3_regions_departments_institutions_referrals like '%Region' ".$cardequery.$licensequery.$regionQuery;
        }
        $query .= " GROUP BY data ";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }


}

