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

        //creating forms array
        $formsArray = array();
        $formString="";
        foreach($forms as $form){
            $formsArray[] = $form->getId();
            $formString .= $form->getId()."_";
        }

        $regionArray = array();
        $regionString = "";
        foreach($region as $prof){
            $regionArray[] = $prof;
            $regionString .= $prof."_";
        }

        //creating carde string for excel export
        $cardetring= "";
        $cardetring .= $NursingCadre;
        //creating licence string for excel export
        $licencestring= "";
        $licencestring .= $licence;

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
                    'forms'   => $formString,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize(array_slice($departmentName,0,25),'json'),
                    'departmentData' => $serializer->serialize(array_slice($departmentData,0,25),'json'),
                    'departmentName1' => $serializer->serialize(array_slice($departmentName,24,25),'json'),
                    'departmentData1' => $serializer->serialize(array_slice($departmentData,24,25),'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                    'carde'    => $cardetring,
                    'licence'  => $licencestring,
                    'regionString' => $regionString
                ));
        }elseif(count($departmentData) > 50){
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $formString,
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
                    'carde'    => $cardetring,
                    'licence'  => $licencestring,
                    'regionString' => $regionString
                ));
        }elseif(count($departmentData) > 75){
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $formString,
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
                    'carde'    => $cardetring,
                    'licence'  => $licencestring,
                    'regionString' => $regionString
                ));
        }else{
            return $this->render(
                'HrisNursingBundle:NursingDistribution:employee.html.twig',
                array(
                    'results' => $results,
                    'forms'   => $formString,
                    'title'   => $title,
                    'levelchoice'=> $organisationUnit,
                    'licensetitle' => $licensetitle,
                    'departmentName' => $serializer->serialize($departmentName,'json'),
                    'departmentData' => $serializer->serialize($departmentData,'json'),
                    'chatType'     => $chatType,
                    'count'  => count($departmentData),
                    'carde'    => $cardetring,
                    'licence'  => $licencestring,
                    'regionString' => $regionString
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
    private function nursingEngine($regionArray, $formArray=array(),$NursingCadre,$licence,$organisationUnit)
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
            $query = "SELECT R.firstname, R.middlename, R.surname, R.designation,R.dob, R.sex, R.edu_evel, R.check_no, R.department, R.employment_status, R.level5_facility ,R.retirementdistribution ";
            }else{
            $query = "SELECT R.firstname, R.middlename, R.surname, R.designation,R.dob, R.sex, R.edu_evel, R.check_no, R.department, R.employment_status, R.level5_facility ,R.retirementdistribution ";
            }
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        if(count($formArray) != 0){
            $query .= " WHERE V.form_id IN (".implode(",", $formArray).") AND R.profession = 'Nurse'  AND R.level3_regions_departments_institutions_referrals like '%Region' ".$cardequery.$licensequery.$regionQuery;
        }else{
            $query .= " WHERE R.profession = 'Nurse' AND R.level3_regions_departments_institutions_referrals like '%Region' ".$cardequery.$licensequery.$regionQuery;
        }
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
     * @Route("/download", name="report_nurse_distribution_download")
     * @Method("GET")
     * @Template()
     */
    public function downloadAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $organisationUnit =$request->query->get('levelchoice');
        $formsId = $request->query->get('forms');
        $regions = $request->query->get('regionString');
        $title = $request->query->get('title');
        $licence = $request->query->get('licence');
        $carde = $request->query->get('carde');

        $formsArray = explode("_",$formsId);
        array_pop($formsArray);

        $regionArray = explode("_",$regions);
        array_pop($regionArray);

        $results = $this->nursingRecords($regionArray, $formsArray,$carde,$licence,$organisationUnit);

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
            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:C2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:C1');
            $excelService->getActiveSheet()->mergeCells('A2:C2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:C4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Organization Unit')
                ->setCellValue($column.$row, 'Nurses');

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

        $excelService->getActiveSheet()->setTitle($title);


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
     * @Route("/records", name="distribution_nursing_download_records")
     * @Method("GET")
     * @Template()
     */
    public function recordsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $organisationUnit =$request->query->get('levelchoice');
        $formsId = $request->query->get('forms');
        $regions = $request->query->get('regionString');
        $title = $request->query->get('title');
        $licence = $request->query->get('licence');
        $carde = $request->query->get('carde');


        //Get the objects from the the variables

        $formsArray = explode("_",$formsId);
        array_pop($formsArray);

        $regionArray = explode("_",$regions);
        array_pop($regionArray);

        $results = $this->nursingEngine($regionArray, $formsArray,$carde,$licence,$organisationUnit);
        //$results = $this->recordsEngine($organisationUnit, $forms, $profession, $leaves, $withLowerLevels,$reportType,$startdate,$enddate);

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
