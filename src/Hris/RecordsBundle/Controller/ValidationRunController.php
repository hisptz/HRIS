<?php
/**
 * Created by JetBrains PhpStorm.
 * User: benny
 * Date: 9/24/13
 * Time: 9:41 PM
 * To change this template use File | Settings | File Templates.
 */


namespace Hris\RecordsBundle\Controller;

use Symfony\Component\Form\Tests\Extension\Core\DataTransformer\BooleanToStringTransformerTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\RecordsBundle\Entity\Training;
use Hris\RecordsBundle\Form\ValidationRunType;
use Hris\RecordsBundle\Entity\Record;
use Hris\OrganisationunitBundle\Entity\OrganisationunitLevel;
use Hris\FormBundle\Entity\Field;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\ORM\EntityManager;
use Hris\DataQualityBundle\Entity\Validation;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validation controller.
 *
 * @Route("/validation")
 */
class ValidationRunController extends Controller
{
    /**
     * Lists all Validation entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDVALIDATION_VALIDATE")
     * @Route("/run", name="validation_run")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $validationRunForm = $this->createForm(new ValidationRunType(), null, array('em' => $this->getDoctrine()->getManager()));

        return array(
            'validationRunForm' => $validationRunForm->createView(),
        );

    }

    /**
     * Displays the validation results.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDVALIDATION_VALIDATE")
     * @Route("/result/",name="validation_result")
     * @Method("POST")
     * @Template("HrisRecordsBundle:ValidationRun:validationResult.html.twig")
     */
    public function validateAction(Request $request)
    {
        $validationRunForm = $this->createForm(new ValidationRunType(), null, array('em' => $this->getDoctrine()->getManager()));

        if ($request->getMethod() == 'POST') {

            $validationRunForm->bind($request);
            $validationValues = $validationRunForm->getData();

            //get selected values
            $organisationunitid = $validationValues['organisationunit'];
            $forms = $validationValues['forms'];
            $selectedValidations = $validationValues['validations'];


            //$organisationunitLevel=$validationValues['organisationunitLevel'];
            $withLowerLevels = $validationValues['withLowerLevels'];


            //getting the Organisationunit object
            $entityManager = $this->getDoctrine()->getManager();
            $organisationUnitObject = $entityManager->getRepository('HrisOrganisationunitBundle:Organisationunit')->findOneBy(array('longname'=> (string)$organisationunitid));

            //Checking if the user what to print data for unit under the selected one.
            if ($withLowerLevels == 1){
                $orgunitChildren = $entityManager->getRepository('HrisOrganisationunitBundle:Organisationunit')->getAllChildren($organisationUnitObject);

                foreach($orgunitChildren as $key => $unit){
                    $orgunitIds[] = $unit[0]['id'];;
                }
            }else{
                $orgunitIds = array(1=>$organisationUnitObject->getId());
            }

            //getting the forms object
            $formIds = "";
            foreach($forms as $key=>$formObjects){
                $formObject = $formObjects;
                $formIds .= "_".$formObjects->getId();
            }

            //getting the Validation object
            foreach($selectedValidations as $key=>$validations){
                $validationObject = $validations;
            }

            //title of the Validation
            $title = "Data Validation Report for Employees directly under " . $organisationUnitObject->getLongname();

            /*
             * getting all fields for use with the title:
              */

            $leftExpTitle = '';
            $validationFault = null;
            $rightExpTitle = '';

            /*
            * Getting Fields with Compulsory Elements
            */
            $compulsoryFields = $entityManager->getRepository('HrisFormBundle:Field')->findBy(array('compulsory' => 'TRUE'));

            if (!empty($compulsoryFields)) {
                foreach ($compulsoryFields as $key => $fieldObj) {
                    $compulsory[$fieldObj->getId()] = $fieldObj->getName();
                }
            }

            //Retrive all validations
            foreach ($selectedValidations as $selectedValidation) {
                $getValidation[] = $entityManager->getRepository('HrisDataQualityBundle:Validation')->findOneBy(array('id' => $selectedValidation));
            }

            $count = 0;
            $emptyFields = '';



        }




        return array(
            'title' => $title,
            'form' => $formObject,
            'forms'=>$formIds,
            'emptyFields' => $emptyFields,
            'compulsory' => $compulsory,
            'getValidations' => $getValidation,
            'withLowerLevel' => $withLowerLevels,
            'organisationunitid' => $organisationUnitObject

        );

    }

    /**
     * Displays the validation results.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDVALIDATION_VALIDATE")
     * @Route("/result/{id}", requirements={"id"="\d+"}, name="one_validation_result")
     * @Method("GET")
     */
    public function ajaxAction(Request $request, $id)
    {
        $request = $this->get('request');
        $forms=$request->query->get('forms');
        $orgunit = $request->query->get('orgunit');
        $withlowerlevel = $request->query->get('withLower');
        $entityManager = $this->getDoctrine()->getManager();
        $organisationUnit = $entityManager->getRepository('HrisOrganisationunitBundle:Organisationunit')->findOneBy(array('id'=> $orgunit));
        $resourceTableName = "_resource_all_fields";
        $validation = $entityManager->getRepository('HrisDataQualityBundle:Validation')->findOneBy(array('id' => $id));
        $forms = explode("_",$forms);
        array_shift($forms);

        $leftExpTitle = '';
        $rightExpTitle = '';
        $getLeftExpression = $validation->getLeftExpression();
        $getRightExpression = $validation->getRightExpression();

        //Getting all the fields
        $fieldObjects = $entityManager->getRepository('HrisFormBundle:Field')->findAll();

        foreach ($fieldObjects as $key => $fieldObj) {

          $param = "#{" . $fieldObj->getName() . "}";
         /*
         * Left Expression title
         */
            if (strstr($getLeftExpression, $param)) {
                $leftExpTitle = $fieldObj->getName();
            }
            /*
             * right Expression title
             */
            if (strstr($getRightExpression, $param)) {
                $rightExpTitle = $fieldObj->getName();
            }
        }
        $getLeftExpression = $validation->getLeftExpression();
        $getRightExpression = $validation->getRightExpression();
        $attachquery = "";
        $operator = $validation->getOperator();
        if(strcspn($getLeftExpression , '0123456789') == strlen($getLeftExpression) && strcspn($getRightExpression , '0123456789') == strlen($getRightExpression)){
            $attachquery = " AND R.".$leftExpTitle ." IS NOT NULL  AND R.".$rightExpTitle ." IS NOT NULL AND R.".$rightExpTitle ." ".$operator." R.".$leftExpTitle." ";
        }else{
            if(is_numeric($getRightExpression) && strcspn($getLeftExpression , '0123456789') == strlen($getLeftExpression)){
                $attachquery =" AND EXTRACT(year FROM age(".$leftExpTitle.")) ".$operator." ".$getRightExpression;
            }else{
                $attachquery = " AND R.id = 0";
            }


        }

        $leftHandValue = $this->calculator($getLeftExpression);
        $rightHandValue = $this->calculator($getRightExpression);
        $operator = $validation->getOperator();

//        switch ($operator) {
//            case '==':
//                if ($leftHandValue == $rightHandValue) {
//                    $attachquery ="'$leftHandValue' == '$rightHandValue'";
//                }
//                break;
//
//            case '!=':
//                if ($leftHandValue != $rightHandValue) {
//                    $attachquery ="'$leftHandValue' != '$rightHandValue'";
//                }
//                break;
//
//            case '>':
//                if ($leftHandValue > $rightHandValue) {
//                    $attachquery ="'$leftHandValue' > '$rightHandValue'";
//                }
//                break;
//
//            case '<':
//                if ($leftHandValue < $rightHandValue) {
//                    $attachquery ="'$leftHandValue' < '$rightHandValue'";
//                }
//                break;
//
//            case '>=':
//                if ($leftHandValue >= $rightHandValue) {
//                    $attachquery ="'$leftHandValue' >= '$rightHandValue'";
//                }
//                break;
//
//            case '<=':
//                if ($leftHandValue <= $rightHandValue) {
//                    $attachquery ="'$leftHandValue' <= '$rightHandValue'";
//                }
//                break;
//        }
        //Query all lower levels units from the passed orgunit
        if($withlowerlevel){
            $allChildrenIds = "SELECT hris_organisationunitlevel.level ";
            $allChildrenIds .= "FROM hris_organisationunitlevel , hris_organisationunitstructure ";
            $allChildrenIds .= "WHERE hris_organisationunitlevel.id = hris_organisationunitstructure.level_id AND hris_organisationunitstructure.organisationunit_id = ". $organisationUnit->getId();
            $subQuery = "V.organisationunit_id = ". $organisationUnit->getId() . " OR ";
            $subQuery .= " ( L.level >= ( ". $allChildrenIds .") AND S.level".$organisationUnit->getOrganisationunitStructure()->getLevel()->getLevel()."_id =".$organisationUnit->getId()." )";
        }else{
            $subQuery = "V.organisationunit_id = ". $organisationUnit->getId();
        }

        //Query all history data and count by field option
        $query = "SELECT R.firstname, R.middlename, R.surname,R.dob,R.first_appointment,R.last_promo, R.level5_facility ";
        $query .= "FROM ".$resourceTableName." R ";
        $query .= "INNER JOIN hris_record as V on V.instance = R.instance ";
        $query .= "INNER JOIN hris_organisationunitstructure as S on S.organisationunit_id = V.organisationunit_id ";
        $query .= "INNER JOIN hris_organisationunitlevel as L on L.id = S.level_id ";
        $query .= " WHERE V.form_id IN (".implode(",", $forms).")".$attachquery;

        $query .= " AND (". $subQuery .")";
        $query .= " ORDER BY R.firstname ASC";

//        echo $query; die();
        //get the records
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        //return $report;

        if($id!=""){//if the user has written his name
            $greeting='Hello '.$validation->getName.'. How are you today?';
            $return=$report;
        }
        else{
            $return=array("responseCode"=>400, "greeting"=>"You have to write your name!");
        }

        $return=json_encode($return);//jscon encode the array
        return new Response($return,200,array('Content-Type'=>'application/json'));//make sure it has the correct content type
    }
    /**
     * Get all values from specific key in a multidimensional array
     *
     * @param $key string
     * @param $arr array
     * @return null|string|array
     */

     function rectify($exp, $mod = "+") {

        $res = $this->recCalc($exp);
        debug("Pre rectify", $res);
        if ($mod == '-') {
            $res *= - 1;
        }
        debug("Post rectify", $res);
        return $res;
    }

    function do_error($str) {
        die($str);
        return false;
    }

    function recCalc($inp) {

        $this->debug("RecCalc input", $inp);

        $p = str_split($inp);
        $level = 0;

        foreach ($p as $num) {
            if ($num == '(' && ++$level == 1) {
                $num = 'BABRAX';
            } elseif ($num == ')' && --$level == 0) {
                $num = 'DEBRAX';
            }
            $res[] = $num;
        }

        if ($level != 0) {
            return do_error('Chyba: špatný počet závorek');
        }

        $res = implode('', $res);

        $res = preg_replace('#([\+\-]?)BABRAX(.+?)DEBRAX#e', "rectify('\\2', '\\1')", $res);

        $this->debug("After parenthesis proccessing", $res);

        preg_match_all('#[+-]?([^+-]+)#', $res, $ar, PREG_PATTERN_ORDER);

        for ($i = 0; $i < count($ar[0]); $i++) {
            $last = substr($ar[0][$i], -1, 1);
            if ($last == '/' || $last == '*' || $last == '^' || $last == 'E') {
                $ar[0][$i] = $ar[0][$i] . $ar[0][$i + 1];
                unset($ar[0][$i + 1]);
            }
        }

        $result = 0;
        foreach ($ar[0] as $num) {
            $result += $this->multi($num);
        }
        $this->debug("RecCalc output", $result);
        return $result;
    }

    function multi($inp) {
        $this->debug("Multi input", $inp);

        $inp = explode(' ', preg_replace('/([\*\/\^])/', ' \\1 ', $inp));

        foreach ($inp as $va) {
            if ($va != '*' && $va != '/' && $va != '^') {
                $v[] = (float) $va;
            } else {
                $v[] = $va;
            }
        }
        $inp = $v;
        //predpokladame, ze prvni prvek je cislo, ktere budeme dale nasobit
        $res = $inp[0];
        for ($i = 1; $i < count($inp); $i++) {

            if ($inp[$i] == '*') {
                $res *= $inp[$i + 1];
            } elseif ($inp[$i] == '/') {
                if ($inp[$i + 1] == 0)
                    do_error('mathematical error');

                $res /= $inp[$i + 1];
            } elseif ($inp[$i] == '^') {
                $res = pow($res, $inp[$i + 1]);
            }
        }
        $this->debug("Multi output", $res);
        return $res;
    }

    function debug($msg, $var) {
        if (isset($_POST['out']) && $_POST['out'] == '1') {
            echo "\n" . $msg . ": " . $var;
        }
    }

    function calculator($input){

        $inp = preg_replace(array('/\s+/', '/Pi/', '/e/', '/T/', '/G/', '/M/', '/k/', '/m/', '/u/', '/n/', '/p/', '/f/'),
            array('', M_PI, exp(1), '*' . 1e12, '*' . 1e9, '*' . 1e6, '*' . 1e3, '*' . 1e-3, '*' . 1e-6, '*' . 1e-9, '*' . 1e-12, '*' . 1e-15),
            $input);

        $result = $this->recCalc($inp);
        return $result;

    }

}





