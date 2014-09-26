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
 * @author Ismail Yusuf Koleleni <ismailkoleleni@gmail.com>
 * @author Leonard C Mpande <leo.august27@gmail.com>
 *
 */
namespace Hris\TrainingBundle\Controller;

use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Hris\FormBundle\Entity\Form;
use Hris\FormBundle\Entity\Field;
use Hris\TrainingBundle\Form\ReportTrainingType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\ReportsBundle\Entity\Report;
use Hris\ReportsBundle\Form\ReportType;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Zend\Json\Expr;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Report HistoryTraining controller.
 *
 * @Route("/trainingreports")
 */
class TrainingReportController extends Controller
{

    /**
     * Show Report HistoryTraining
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="trainingreports")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {

        $historytrainingForm = $this->createForm(new ReportTrainingType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));

        return array(
            'historytrainingForm'=>$historytrainingForm->createView(),
        );
    }

    /**
     * Generate aggregated reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/", name="report_trainingreports_generate")
     * @Method("PUT")
     * @Template()
     */
    public function generateAction(Request $request)
    {
        $historytrainingForm = $this->createForm(new ReportTrainingType($this->getUser()),null,array('em'=>$this->getDoctrine()->getManager()));
        $historytrainingForm->bind($request);

        if ($historytrainingForm->isValid()) {

            $historytrainingFormData = $historytrainingForm->getData();
            $organisationUnit = $historytrainingFormData['organisationunit'];
            $forms = $historytrainingFormData['forms'];
            $reportType = $historytrainingFormData['reportType'];
            $withLowerLevels = $historytrainingFormData['withLowerLevels'];
            $graphType = $historytrainingFormData['graphType'];
            $startdate = $historytrainingFormData['startdate'];
            $enddate =  $historytrainingFormData['enddate'];
            $trainings = $historytrainingFormData['Trainings'];


            $groups = Array('0'=>00);

            foreach($trainings as $group) {
                $groups_ids[] = $group->getId();
                $group_names[] = $group->getCoursename();

            }

            $groups[0] =  $groups_ids;
            $groups[1] =  $group_names;
            $formIds = Array('0'=>00);

            foreach($forms as $form) {
                $formIds[] = $form->getId();
            }

        }



      if($reportType =="facilitators" || $reportType == "participants" || $reportType == "trainings"){
          $groups_results = $this->aggregationEngine($organisationUnit,$groups, $formIds,$reportType, $withLowerLevels,$startdate,$enddate);

            $groups_series = array();

            foreach($groups_results as $results){

                $data = array();
                if(is_array($results[1])){

                    foreach($results[1] as $result){

                        $categories[] = $result['data'];
                        $data[] =  json_decode('[' . $result['total'] . ']', true);

                        if($graphType == 'pie'){
                            $piedata[] = array('name' => $result['data'],'y' => $result['total']);
                        }
                    }

                }else{

                }


                $groups_series[] =
                array(
                    'name'  => $results[0],
                    'data'  => $data,
                );


            }

          $cats = array_map("unserialize", array_unique(array_map("serialize", $categories)));
          $categories = array();
          foreach($cats as $cat){
              $categories[] = $cat;
          }

      //   echo $categories = json_encode($categories);die();

            $series = $groups_series;
            if ($withLowerLevels){
                $withLower = " with lower levels";
            }

          if($reportType=="facilitators"){
              $formatterLabel = 'Facilitators';
              $subtitle = "Facilitators";

          }elseif($reportType=="participants"){
              $formatterLabel = 'Participants';
              $subtitle = "Participants";

          }elseif($reportType=="trainings"){
              $formatterLabel = 'Trainings';
              $subtitle = "Trainings";

          }




      }

        //check which type of chart to display
        if($graphType == "bar"){
            $graph = "column";
        }elseif($graphType == "line"){
            $graph = "spline";
        }else{
            $graph = "pie";
        }
        //set the title and sub title
        $title = $subtitle." Distribution Report";


        $yData = array(
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value + "" }'),
                    'style'     => array('color' => '#0D0DC1')
                ),
                'title' => array(
                    'text'  => $subtitle,
                    'style' => array('color' => '#0D0DC1')
                ),
                'opposite' => true,
            ),
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value + "" }'),
                    'style'     => array('color' => '#AA4643')
                ),
                'gridLineWidth' => 1,
                'title' => array(
                    'text'  => $subtitle,
                    'style' => array('color' => '#AA4643')
                ),
            ),
        );

        $dashboardchart = new Highchart();
        $dashboardchart->chart->renderTo('chart_placeholder_historytraining'); // The #id of the div where to render the chart
        $dashboardchart->chart->type($graph);
        $dashboardchart->title->text($title);
        $dashboardchart->subtitle->text($organisationUnit->getLongname().$withLower);
        $dashboardchart->xAxis->categories($categories);

        $dashboardchart->yAxis($yData);
        $dashboardchart->legend->enabled(true);

        $formatter = new Expr('function () {
                 var unit = {

                     "'.$formatterLabel.'" : "'. strtolower($formatterLabel).'",

                 }[this.series.name];
                 if(this.point.name) {
                    return ""+this.point.name+": <b>"+ this.y+"</b> "+ this.series.name;
                 }else {
                    return this.x + ": <b>" + this.y + "</b> " + this.series.name;
                 }
             }');
        $dashboardchart->tooltip->formatter($formatter);
        if($graphType == 'pie') $dashboardchart->plotOptions->pie(array('allowPointSelect'=> true,'dataLabels'=> array ('format'=> '<b>{point.name}</b>: {point.percentage:.1f} %')));
        $dashboardchart->series($series);

        $forms_id = "";
        foreach($formIds as $ids){
            $forms_id .="_".$ids;
        }

        return array(
            'chart'=>$dashboardchart,
            'data'=>$data,
            'categories'=>$categories,
            'organisationUnit' => $organisationUnit,
            'forms' => $forms,
            'formIds' => $forms_id,
            'groups' => $groups,
            'reportType' => $reportType,
            'withLowerLevels' => $withLowerLevels,
            'title'=> $title,
            'result2'=>$result,
        );
    }


    /**
     * Aggregation Engine
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param Field $fields
     * @param $reportType
     * @param $withLowerLevels
     * @return mixed
     */
    private function aggregationEngine(Organisationunit $organisationUnit,$groups, $forms,$reportType, $withLowerLevels,$startdate,$enddate)
    {

        $entityManager = $this->getDoctrine()->getManager();

        if($startdate !=NULL && $enddate !=NULL){
            $dateSubquery = " and hris_traininginstance.startdate = '".$startdate."'";

        }else{
            $dateSubquery="";
        }
        if ($reportType == "facilitators") {

            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds  = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery  = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }


            $groups_results = array();
            $i=0;
            foreach($groups[0] as $individual_group){

                $query  = "SELECT count(F.record_id) as total , date_part('year',startdate) as data ";
                $query .= "FROM hris_instanceFacilitator F ";
                $query .= "INNER JOIN hris_traininginstance as I on I.id = F.instance_id ";
                $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
                $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
                $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
                $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
                $query .= "where V.form_id  in (".implode(",",$forms).")  and I.region= '".$organisationUnit->getLongName()."' and   training_id =".$individual_group."  GROUP BY I.startdate";


                $results[0] = $groups[1][$i];
                $results[1] = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
                $groups_results[] = $results;
                $i++;
            }


        }

        if ($reportType == "participants") {

            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds  = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery  = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            $groups_results = array();
            $i=0;
            foreach($groups[0] as $individual_group){

                $query  = "SELECT count(F.record_id) as total , date_part('year',startdate) as data ";
                $query .= "FROM hris_instance_records F ";
                $query .= "INNER JOIN hris_traininginstance as I on I.id = F.instance_id ";
                $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
                $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
                $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
                $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
                $query .= "where V.form_id  in (".implode(",",$forms).") and I.region= '".$organisationUnit->getLongName()."' and training_id =".$individual_group."  GROUP BY I.startdate";


                $results[0] = $groups[1][$i];
                $results[1] = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
                $groups_results[] = $results;
                $i++;
            }


        } if ($reportType == "trainings") {

            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds  = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery  = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }

            $groups_results = array();
            $i=0;
            foreach($groups[0] as $individual_group){

                $query  = "SELECT count(training_id) as total , date_part('year',startdate) as data ";
                $query .= "FROM hris_traininginstance I ";
                $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
                $query .= "INNER JOIN hris_instance_records as F on F.instance_id = I.id ";
                $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
                $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
                $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
                $query .= "INNER JOIN hris_organisationunit as O on O.longname = I.region ";
                $query .= "where V.form_id  in (".implode(",",$forms).") and I.region= '".$organisationUnit->getLongName()."' and I.training_id =".$individual_group."  GROUP BY I.startdate";

                $results[0] = $groups[1][$i];
                $results[1] = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
                $groups_results[] = $results;
                $i++;
            }


        }



        //get the records
        return $groups_results;


    }

    /**
     * Records Engine
     *
     * @param Organisationunit $organisationUnit
     * @param Form $forms
     * @param Field $fields
     * @param $reportType
     * @param $withLowerLevels
     * @return mixed
     */
    private function recordsEngine(Organisationunit $organisationUnit,$groups, $forms,$reportType, $withLowerLevels,$startdate,$enddate)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";

        if ($reportType == "trainings") {
            //Query all lower levels units from the passed orgunit
            if($withLowerLevels){
                $allChildrenIds  = "SELECT hris_organisationunitlevel.level ";
                $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
                $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
                $subQuery  = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
                $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
            }else{
                $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
            }


            $query  = "SELECT T.coursename as coursename,I.region as region,I.district as district,I.venue as venue,I.startdate as startdate,I.enddate as enddate ";
            $query .= "FROM hris_traininginstance I ";
            $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
            $query .= "INNER JOIN hris_instance_records as F on F.instance_id = I.id ";
            $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            $query .= "INNER JOIN hris_organisationunit as O on O.longname = I.region ";
            $query .= "where V.form_id  in (".implode(",",$forms).")";
            $query .="and I.training_id in (".implode(",",$groups[0]).") and I.region= '".$organisationUnit->getLongName()."'  GROUP BY T.coursename,I.region,I.district,I.venue,I.startdate,I.enddate ORDER BY I.startdate DESC";



                $results = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();


//            }


        }

        if ($reportType == "participants") {

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
            $query  = "SELECT * ";
            $query .= "FROM hris_instance_records F ";
            $query .= "INNER JOIN hris_traininginstance as I on I.id = F.instance_id ";
            $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
            $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            $query .= "INNER JOIN hris_organisationunit as O on O.longname = I.region ";
            $query .= "where V.form_id  in (".implode(",",$forms).") and I.region= '".$organisationUnit->getLongName()."' and I.training_id in (".implode(",",$groups[0]).") GROUP BY  I.startdate,F.id,I.id,T.id,V.id,R.id,S.id,L.id,O.id";

                $results = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();



        }
        if ($reportType == "facilitators") {

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
            $query  = "SELECT * ";
            $query .= "FROM hris_instanceFacilitator F ";
            $query .= "INNER JOIN hris_traininginstance as I on I.id = F.instance_id ";
            $query .= "INNER JOIN hris_trainings as T on T.id = I.training_id ";
            $query .= "INNER JOIN hris_record as V on V.id = F.record_id ";
            $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
            $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
            $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
            $query .= "INNER JOIN hris_organisationunit as O on O.longname = I.region ";
            $query .= "where V.form_id  in (".implode(",",$forms).") and I.region= '".$organisationUnit->getLongName()."' and I.training_id in (".implode(",",$groups[0]).") GROUP BY  I.startdate,F.id,I.id,T.id,V.id,R.id,S.id,L.id,O.id";

                $results = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();



        }


        return $results;
    }

    /**
     * Download History reports
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_DOWNLOAD")
     * @Route("/download", name="report_training_download")
     * @Method("GET")
     * @Template()
     */
    public function downloadAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $organisationUnitid =$request->query->get('organisationUnit');
        $forms = explode("_",$request->query->get('forms'));
        array_shift($forms);
        $groups = $request->query->get('groups');
        $reportType = $request->query->get('reportType');
        $withLowerLevels =$request->query->get('withLowerLevels');
        $trainings = $request->query->get('Trainings');
        $startdate = "";
        $enddate   = "";


        //Get the objects from the the variables

        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);

        $group_results = $this->aggregationEngine($organisationUnit,$groups, $forms,$reportType, $withLowerLevels,$startdate,$enddate);



        //create the title
        if($reportType=="facilitators"){
            $formatterLabel = 'Facilitators';
            $subtitle = "Facilitators";

        }elseif($reportType=="participants"){
            $formatterLabel = 'Participants';
            $subtitle = "Participants";

        }elseif($reportType=="trainings"){
            $formatterLabel = 'Trainings';
            $subtitle = "Trainings";

        }
        $title = $subtitle. " Distribution Report ".$organisationUnit->getLongname();

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
        if ($reportType == "trainings") {

            /*print_r($results);exit;
            foreach($results as $result){
                $keys[$result[strtolower($fields->getName())]][$result[strtolower($fieldsTwo->getName())]] = $result['total'];
                //$categoryKeys[$result[strtolower($fields->getName())]] = $result['total'];

            }*/

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:D2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:D1');
            $excelService->getActiveSheet()->mergeCells('A2:D2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:D4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, "Trainings")
                ->setCellValue($column++.$row, "Year")
                ->setCellValue($column.$row, 'Value');

            /*$fieldOptions = $em->getRepository('HrisFormBundle:FieldOption')->findBy(array('field'=>$fieldsTwo));

            foreach ($fieldOptions as $fieldOption) {
                $excelService->setActiveSheetIndex(0)->setCellValue($column++.$row, $fieldOption->getValue());
            }*/




            $groups_series = array();
            $i = 0;
            foreach($group_results as $results){

                $data = array();
                if(is_array($results[1])){




                    $p = 0;
                    foreach($results[1] as $result){

                        $categories[] = $result['data'];
                        $data[] =  json_decode('[' . $result['total'] . ']', true);

                        $cats = array_map("unserialize", array_unique(array_map("serialize", $categories)));
                        $categories = array();
                        foreach($cats as $cat){
                            $categories[] = $cat;
                        }


                        $column = 'A';//return to the 1st column
                        $row++; //increment one row

                        //format of the row
                        if (($row % 2) == 1)
                            $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format1);
                        else
                            $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format2);
                        $excelService->setActiveSheetIndex(0)
                            ->setCellValue($column++.$row, ++$i)
                            ->setCellValue($column++.$row, $results[0])
                            ->setCellValue($column++.$row, $result['data'])
                            ->setCellValue($column++.$row, $result['total']);
                        $p++;




                    }

                }else{

                }


                $groups_series[] =
                    array(
                        'name'  => $results[0],
                        'data'  => $data,
                    );


            }

            $cats = array_map("unserialize", array_unique(array_map("serialize", $categories)));
            $categories = array();
            foreach($cats as $cat){
                $categories[] = $cat;
            }



        }
        if ($reportType == "participants" ){
            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:D2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:D1');
            $excelService->getActiveSheet()->mergeCells('A2:D2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:D4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Training')
                ->setCellValue($column++.$row, 'Year')
                ->setCellValue($column.$row, 'Value');

            //write the values
            $i =1; //count the row

            $groups_series = array();
            $i = 0;
            foreach($group_results as $results){

                $data = array();
                if(is_array($results[1])){

                    $p = 0;
                    foreach($results[1] as $result){

                        $categories[] = $result['data'];
                        $data[] =  json_decode('[' . $result['total'] . ']', true);

                        $cats = array_map("unserialize", array_unique(array_map("serialize", $categories)));
                        $categories = array();
                        foreach($cats as $cat){
                            $categories[] = $cat;
                        }


                        $column = 'A';//return to the 1st column
                        $row++; //increment one row

                        //format of the row
                        if (($row % 2) == 1)
                            $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format1);
                        else
                            $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format2);
                        $excelService->setActiveSheetIndex(0)
                            ->setCellValue($column++.$row, ++$i)
                            ->setCellValue($column++.$row, $results[0])
                            ->setCellValue($column++.$row, $result['data'])
                            ->setCellValue($column++.$row, $result['total']);
                        $p++;




                    }

                }else{

                }
            }
        }
        if ($reportType == "facilitators" ){
            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:D2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:D1');
            $excelService->getActiveSheet()->mergeCells('A2:D2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:D4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Training')
                ->setCellValue($column++.$row, 'Year')
                ->setCellValue($column.$row, 'Value');

            //write the values
            $i =1; //count the row

            $groups_series = array();
            $i = 0;
            foreach($group_results as $results){

                      $data = array();
                     if(is_array($results[1])){

                         $p = 0;
                         foreach($results[1] as $result){

                             $categories[] = $result['data'];
                             $data[] =  json_decode('[' . $result['total'] . ']', true);

                             $cats = array_map("unserialize", array_unique(array_map("serialize", $categories)));
                             $categories = array();
                             foreach($cats as $cat){
                                 $categories[] = $cat;
                             }


                             $column = 'A';//return to the 1st column
                             $row++; //increment one row

                             //format of the row
                             if (($row % 2) == 1)
                                 $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format1);
                             else
                                 $excelService->getActiveSheet()->getStyle($column.$row.':D'.$row)->applyFromArray($text_format2);
                             $excelService->setActiveSheetIndex(0)
                                 ->setCellValue($column++.$row, ++$i)
                                 ->setCellValue($column++.$row, $results[0])
                                 ->setCellValue($column++.$row, $result['data'])
                                 ->setCellValue($column++.$row, $result['total']);
                             $p++;




                         }

                     }else{

                     }
            }
        }

        $excelService->getActiveSheet()->setTitle('Training-History Report');


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
     * @Route("/records", name="report_training_download_cadre")
     * @Method("GET")
     * @Template()
     */
    public function recordsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $forms = explode("_",$request->query->get('forms'));
        array_shift($forms);
        $organisationUnitid =$request->query->get('organisationUnit');

        $reportType = $request->query->get('reportType');
        $withLowerLevels =$request->query->get('withLowerLevels');
//        $fieldsId   =$request->query->get('fields');
        $groups     =$request->query->get('groups');
        $startdate ="";
        $enddate="";
        //Get the objects from the the variables

        $organisationUnit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($organisationUnitid);


        $results = $this->recordsEngine($organisationUnit,$groups, $forms,$reportType, $withLowerLevels,$startdate,$enddate);


        if( $reportType == "trainings"){

            $subtitle = " Trainings";
        }
        if( $reportType == "participants"){

            $subtitle = " Participants ";
        }
        if( $reportType == "facilitators"){

            $subtitle = " Facilitators ";
        }

        $title = $subtitle. " Report ".$organisationUnit->getLongname();

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

        //Start populating excel with data
        if ($reportType == "trainings") {

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:G2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:G1');
            $excelService->getActiveSheet()->mergeCells('A2:G2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:G4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Course Name')
                ->setCellValue($column++.$row, 'Region')
                ->setCellValue($column++.$row, 'District')
                ->setCellValue($column++.$row, 'Start Date')
                ->setCellValue($column++.$row, 'End Date')
                ->setCellValue($column.$row, 'Venue');



            //write the values
            $i =1; //count the row
                                  $p = 0;
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
                        ->setCellValue($column++.$row, $result['coursename'])
                        ->setCellValue($column++.$row, $result['region'])
                        ->setCellValue($column++.$row, $result['district'])
                        ->setCellValue($column++.$row, $result['startdate'])
                        ->setCellValue($column++.$row, $result['enddate'])
                        ->setCellValue($column.$row, $result['venue']);
                    $p++;
                    /*foreach ($items as $item) {
                        $excelService->setActiveSheetIndex(0)->setCellValue($column++.$row, $item);
                    }*/



            }

        }
        //Start populating excel with data
        if ($reportType == "participants") {

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:H2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:H1');
            $excelService->getActiveSheet()->mergeCells('A2:H2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:H4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Name')
                ->setCellValue($column++.$row, 'Region')
                ->setCellValue($column++.$row, 'District')
                ->setCellValue($column++.$row, 'Course')
                ->setCellValue($column++.$row, 'Start Date')
                ->setCellValue($column++.$row, 'End Date')
                ->setCellValue($column++.$row, 'Venue');

            $i =1; //count the row
                                  $p = 0;
            foreach($results as $result){

                    $column = 'A';//return to the 1st column
                    $row++; //increment one row

                    //format of the row
                    if (($row % 2) == 1)
                        $excelService->getActiveSheet()->getStyle($column.$row.':H'.$row)->applyFromArray($text_format1);
                    else
                        $excelService->getActiveSheet()->getStyle($column.$row.':H'.$row)->applyFromArray($text_format2);
                    $excelService->setActiveSheetIndex(0)
                        ->setCellValue($column++.$row, $i++)
                        ->setCellValue($column++.$row, $result['firstname']." ".$result['middlename']." ".$result['surname'])
                        ->setCellValue($column++.$row, $result['region'])
                        ->setCellValue($column++.$row, $result['district'])
                        ->setCellValue($column++.$row, $result['coursename'])
                        ->setCellValue($column++.$row, $result['startdate'])
                        ->setCellValue($column++.$row, $result['enddate'])
                        ->setCellValue($column++.$row, $result['venue']);
                    $p++;




            }

        }
//Start populating excel with data
        if ($reportType == "facilitators") {

            //apply the styles
            $excelService->getActiveSheet()->getStyle('A1:H2')->applyFromArray($heading_format);
            $excelService->getActiveSheet()->mergeCells('A1:H1');
            $excelService->getActiveSheet()->mergeCells('A2:H2');

            //write the table heading of the values
            $excelService->getActiveSheet()->getStyle('A4:H4')->applyFromArray($header_format);
            $excelService->setActiveSheetIndex(0)
                ->setCellValue($column++.$row, 'SN')
                ->setCellValue($column++.$row, 'Name')
                ->setCellValue($column++.$row, 'Region')
                ->setCellValue($column++.$row, 'District')
                ->setCellValue($column++.$row, 'Course')
                ->setCellValue($column++.$row, 'Start Date')
                ->setCellValue($column++.$row, 'End Date')
                ->setCellValue($column++.$row, 'Venue');

            $i =1; //count the row
                                  $p = 0;
            foreach($results as $result){

                    $column = 'A';//return to the 1st column
                    $row++; //increment one row

                    //format of the row
                    if (($row % 2) == 1)
                        $excelService->getActiveSheet()->getStyle($column.$row.':H'.$row)->applyFromArray($text_format1);
                    else
                        $excelService->getActiveSheet()->getStyle($column.$row.':H'.$row)->applyFromArray($text_format2);
                    $excelService->setActiveSheetIndex(0)
                        ->setCellValue($column++.$row, $i++)
                        ->setCellValue($column++.$row, $result['firstname']." ".$result['middlename']." ".$result['surname'])
                        ->setCellValue($column++.$row, $result['region'])
                        ->setCellValue($column++.$row, $result['district'])
                        ->setCellValue($column++.$row, $result['coursename'])
                        ->setCellValue($column++.$row, $result['startdate'])
                        ->setCellValue($column++.$row, $result['enddate'])
                        ->setCellValue($column++.$row, $result['venue']);
                    $p++;




            }

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
//        die();
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
//$serializer->serialize($fieldNodes,$_format)
        return array(
            'entities' => $results
        );
    }

}
