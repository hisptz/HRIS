<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kelvin Mbwilo & benny
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
        //getting values from url request
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

        //preparing variables to use
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

        //preparing a subquery to attach to main query
        $attachquery = "";
        $operator = $validation->getOperator();
        if(strcspn($getLeftExpression , '0123456789') == strlen($getLeftExpression) && strcspn($getRightExpression , '0123456789') == strlen($getRightExpression)){
            $attachquery = " AND R.".$leftExpTitle ." IS NOT NULL  AND R.".$rightExpTitle ." IS NOT NULL AND R.".$rightExpTitle ." ".$operator." R.".$leftExpTitle." ";
        }else{
            if(strpos($getRightExpression,'#{') !== false){
//                $expressionTouse = explode("{")
                $exp = str_replace("#{","R.",$getRightExpression);
                $exp = str_replace("}","",$exp);
                $attachquery =" AND R.".$leftExpTitle ." IS NOT NULL  AND R.".$rightExpTitle ." IS NOT NULL  AND ".($exp)." ".$operator." R.".$leftExpTitle;
            }else{
                $attachquery = " AND R.".$leftExpTitle ." IS NOT NULL AND ".$getRightExpression." ".$operator." R.".$leftExpTitle." ";
            }
        }
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

        if($id!=""){
            $return=$report;
//            $return=array("result"=>$query);
        }
        $return=json_encode($return);//jscon encode the array
        return new Response($return,200,array('Content-Type'=>'application/json'));//make sure it has the correct content type
    }


}





