<?php
namespace Hris\NursingBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
use Hris\NursingBundle\Form\DepartmentType;
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
* @Route("/nursesDepartmentReport")
*/
class DepartmentController extends Controller
{
    /**
     * Show Leave Report Records Form
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/", name="nursing_department_report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $departmentReportForm = $this->createForm(new DepartmentType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        return array(
            'departmentReportForm'=>$departmentReportForm->createView(),
        );
    }
    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="department_nurses_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $serializer = $this->container->get('serializer');
        $nursingReportForm = $this->createForm(new DepartmentType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        $nursingReportForm->bind($request);


        if ($nursingReportForm->isValid()) {
            $nursingReportFormData = $nursingReportForm->getData();
            $organisationUnit = $nursingReportFormData['organisationunit'];
            $forms = $nursingReportFormData['forms'];
            $licence = $nursingReportFormData['NursesLicencing'];
            $withLowerLevels = $nursingReportFormData['withLowerLevels'];
            $chatType = $nursingReportFormData['chatType'];
            $NursingCadre = $nursingReportFormData['NursingCadre'];
            $startdate = ($nursingReportFormData['startdate'] == "")?"":$nursingReportFormData['startdate']->format('Y-m-d');
            $enddate = ($nursingReportFormData['enddate'] == "")?"":$nursingReportFormData['enddate']->format('Y-m-d');
        }

        //creating array of selected professionals
        $formsArray = array();
        foreach($forms as $form){
            $formsArray[] = $form->getId();
        }

        //craeting a title
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
        $results = $this->nursingRecords($organisationUnit, $formsArray,$withLowerLevels,$startdate,$enddate,$NursingCadre,$licence);
        $serializer = $this->container->get('serializer');
        $departmentName = array();
        $departmentData = array();
        foreach($results as $result){
            $departmentName[] = $result['data'];
            $departmentData[] = $result['total'];
        }
        if($withLowerLevels) {
            $lowertitle =" with Lower Levels";
        }
        return $this->render(
            'HrisNursingBundle:Department:employee.html.twig',
            array(
                'results' => $results,
                'organisationUnit' => $organisationUnit,
                'forms'   => $forms,
                'withLowerLevels' => $lowertitle,
                'title'   => $title,
                'licensetitle' => $licensetitle,
                'departmentName' => $serializer->serialize(array_slice($departmentName,0,10),'json'),
                'departmentData' => $serializer->serialize(array_slice($departmentData,0,10),'json'),
                'departmentName1' => $serializer->serialize(array_slice($departmentName,9,12),'json'),
                'departmentData1' => $serializer->serialize(array_slice($departmentData,9,12),'json'),
                'chatType'     => $chatType,
            ));

    }

    /**
     * return nursing records
     *
     * @param Organisationunit $organisationUnit
     * @param $formArray
     * @param $withLowerLevels
     * @param $startdate
     * @param $enddate
     * @param $NursingCadre
     * @param $licence
     * @return mixed
     */
    private function nursingRecords(Organisationunit $organisationUnit, $formArray=array(), $withLowerLevels,$startdate,$enddate,$NursingCadre,$licence)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";
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
        $query = "SELECT department as data, count(department) as total ";
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse'  AND R.department != '' ".$cardequery.$licensequery;
        }else{
            $query .= " WHERE R.profession = 'Nurse' AND R.department != '' ".$cardequery.$licensequery;
        }
        $query .= " AND (". $subQuery .") ";
        $query .= " GROUP BY data ";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }


}

