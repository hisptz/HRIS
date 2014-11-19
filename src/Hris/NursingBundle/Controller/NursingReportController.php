<?php

namespace Hris\NursingBundle\Controller;

use Hris\LeaveBundle\Form\LeaveReportType;
use Hris\NursingBundle\Form\NursingReportType;
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
        $nursingReportForm = $this->createForm(new NursingReportType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        return array(
            'nursingReportForm'=>$nursingReportForm->createView(),
        );
    }

    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="report_nurses_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $serializer = $this->container->get('serializer');
        $nursingReportForm = $this->createForm(new NursingReportType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
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
        $formString="";
        foreach($forms as $form){
            $formsArray[] = $form->getId();
            $formString .= $form->getId()."_";
        }

        //creating carde string for excel export
        $cardetring= "";
        $cardetring .= $NursingCadre;
        //creating licence string for excel export
        $licencestring= "";
        $licencestring .= $licence;

        //craeting a title
        $licensetitle = "";
        if($licence == "Licensed"){
            $licensetitle = " Licensed ";
        }elseif($licence == "NotLicensed"){
            $licensetitle = "Not Licenced";
        }else{

        }
        $title = "";
        if($NursingCadre != ""){
            $title = $NursingCadre."  ";
        }else{
            $title = "Nursing";
        }

        //Get the Id for the form
//        $formsId = $forms->getId();
            $results = $this->nursingRecords($organisationUnit, $formsArray,$withLowerLevels,$startdate,$enddate,$NursingCadre,$licence);
            return $this->render(
                'HrisNursingBundle:NursingReport:employee.html.twig',
                array(
                    'results' => $results,
                    'organisationUnit' => $organisationUnit,
                    'forms'   => $formString,
                    'withLowerLevels' => $withLowerLevels,
                    'title'   => $title,
                    'licensetitle' => $licensetitle,
                    'startdate'  => $startdate,
                    'enddate'  => $enddate,
                    'carde'    => $cardetring,
                    'licence'  => $licencestring
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
        $query = "SELECT R.firstname, R.middlename, R.surname, R.designation,R.dob, R.sex, R.edu_evel, R.check_no, R.department, R.employment_status, R.level5_facility ,R.retirementdistribution ";
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse' AND employment_status != 'Deceased' ".$cardequery.$licensequery;
        }else{
            $query .= " WHERE R.profession = 'Nurse'  AND employment_status != 'Deceased' ".$cardequery.$licensequery;
        }
        $query .= " AND (". $subQuery .") ";
        $query .= " ORDER BY R.firstname ASC";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }

    /**
     * Download history reports by Cadre
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOADBYCADRE")
     * @Route("/records", name="report_nursing1_download_records")
     * @Method("GET")
     * @Template()
     */
    public function recordsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $organisationUnitid =$request->query->get('organisationUnit');
        $formsId = $request->query->get('forms');
        $title = $request->query->get('title');
        $withLowerLevels =$request->query->get('withLowerLevels');
        $startdate = $request->query->get('startdate');
        $enddate = $request->query->get('enddate');
        $licence = $request->query->get('licence');
        $carde = $request->query->get('carde');


        //Get the objects from the the variables

        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);
        //create a form array
        $formsArray = explode("_",$formsId);
        array_pop($formsArray);
        $results = $this->nursingRecords($organisationUnit, $formsArray,$withLowerLevels,$startdate,$enddate,$carde,$licence);
        //$results = $this->recordsEngine($organisationUnit, $forms, $profession, $leaves, $withLowerLevels,$reportType,$startdate,$enddate);

        //create the title
        $subtitle = $title;


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
            ->setCellValue($column++.$row, 'Date Of Birth')
            ->setCellValue($column++.$row, 'Gender')
            ->setCellValue($column++.$row, 'Education Level')
            ->setCellValue($column++.$row, 'Check Number')
            ->setCellValue($column++.$row, 'Department')
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
                ->setCellValue($column++.$row, $result['dob'])
                ->setCellValue($column++.$row, $result['sex'])
                ->setCellValue($column++.$row, $result['edu_evel'])
                ->setCellValue($column++.$row, $result['check_no'])
                ->setCellValue($column++.$row, $result['department'])
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
}