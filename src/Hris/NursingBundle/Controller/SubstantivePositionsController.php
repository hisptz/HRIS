<?php
namespace Hris\NursingBundle\Controller;

use Hris\NursingBundle\Form\DepartmentType;
use Hris\NursingBundle\Form\SubstantivePositionsType;
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
 * @Route("/nursesSubstantivePositionsReport")
 */
class SubstantivePositionsController extends Controller
{
    /**
     * Show SubstantivePositions Report Records Form
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/", name="nursing_substantive_positions_report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $positonReportForm = $this->createForm(new SubstantivePositionsType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        return array(
            'positionReportForm'=>$positonReportForm->createView(),
        );
    }
    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="nurses_position_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $serializer = $this->container->get('serializer');
        $nursingReportForm = $this->createForm(new SubstantivePositionsType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        $nursingReportForm->bind($request);


        if ($nursingReportForm->isValid()) {
            $nursingReportFormData = $nursingReportForm->getData();
            $organisationUnit = $nursingReportFormData['organisationunit'];
            $forms = $nursingReportFormData['forms'];
            $reportType = $nursingReportFormData['reportType'];
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

        if($withLowerLevels) {
            $lowertitle =" with Lower Levels";
        }

        if($reportType == 'table'){
            $results = $this->tableRecords($organisationUnit, $formsArray,$withLowerLevels,$startdate,$enddate,$NursingCadre,$licence);
            return $this->render(
                'HrisNursingBundle:SubstantivePositions:employeeTable.html.twig',
                array(
                    'results' => $results,
                    'organisationUnit' => $organisationUnit,
                    'forms'   => $forms,
                    'withLowerLevels' => $lowertitle,
                    'title'   => $title,
                    'licensetitle' => $licensetitle
                ));

        }else{
        $results = $this->nursingRecords($organisationUnit, $formsArray,$withLowerLevels,$startdate,$enddate,$NursingCadre,$licence);
        $serializer = $this->container->get('serializer');
        $departmentName = array();
        $departmentData = array();
        foreach($results as $result){
            $departmentName[] = $result['data'];
            $departmentData[] = $result['total'];
        }

        return $this->render(
            'HrisNursingBundle:SubstantivePositions:employee.html.twig',
            array(
                'results' => $results,
                'organisationUnit' => $organisationUnit,
                'forms'   => $forms,
                'withLowerLevels' => $lowertitle,
                'title'   => $title,
                'licensetitle' => $licensetitle,
                'departmentName' => $serializer->serialize($departmentName,'json'),
                'departmentData' => $serializer->serialize($departmentData,'json'),
                'chatType'     => $chatType,
            ));
    }
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
        $query = "SELECT hosp_superlative_post as data, count(hosp_superlative_post) as total ";
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse' AND R.hosp_superlative_post != '' AND R.hosp_superlative_post != 'None'  ".$cardequery.$licensequery;
        }else{
            $query .= " WHERE R.profession = 'Nurse' AND R.hosp_superlative_post != '' AND R.hosp_superlative_post != 'None' ".$cardequery.$licensequery;
        }
        $query .= " AND (". $subQuery .") ";
        $query .= " GROUP BY data ";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
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
    private function tableRecords(Organisationunit $organisationUnit, $formArray=array(), $withLowerLevels,$startdate,$enddate,$NursingCadre,$licence)
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
        $query = "SELECT R.firstname, R.middlename,R.hosp_superlative_post, R.surname, R.designation,R.dob, R.sex, R.edu_evel, R.check_no, R.department, R.employment_status, R.level5_facility ,R.retirementdistribution ";
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse'  AND R.hosp_superlative_post != ''  AND R.hosp_superlative_post != 'None'  ".$cardequery.$licensequery;
        }else{
            $query .= " WHERE R.profession = 'Nurse' AND R.hosp_superlative_post != ''  AND R.hosp_superlative_post != 'None'  ".$cardequery.$licensequery;
        }
        $query .= " AND (". $subQuery .") ";
        $query .= " ORDER BY R.firstname ASC";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return $report;
    }

    /**
     * Download Leave reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOAD")
     * @Route("/download", name="report_substansive_nurse_download")
     * @Method("GET")
     * @Template()
     */
    public function downloadAction(Request $request)
    {
        echo "";exit;
        $em = $this->getDoctrine()->getManager();

        $organisationUnitid =$request->query->get('organisationUnit');
        $reportType = $request->query->get('reportType');
        $withLowerLevels =$request->query->get('withLowerLevels');
        $results = $request->query->get('results');

        echo $results;exit;

        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);


            $subtitle = "";


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
     * Download history reports by Cadre
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOADBYCADRE")
     * @Route("/records", name="report_substansive_nurse_download_records")
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


}


