<?php

namespace Hris\TrainingBundle\Controller;

use Hris\TrainingBundle\Entity\instanceFacilitator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\Traininginstance;
use Hris\TrainingBundle\Form\TraininginstanceType;
use JMS\SecurityExtraBundle\Annotation\Secure;


use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Common\Annotations\True;
use Doctrine\ORM\QueryBuilder as QueryBuilder;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator  as DoctrineHydrator;
use Hris\RecordsBundle\Entity\Record;
use Hris\TrainingBundle\Entity\instanceRecord;
use Hris\TrainingBundle\Entity\instanceTrainer;
use Hris\TrainingBundle\Entity\trainer;
use Hris\RecordsBundle\Form\RecordType;
use Hris\TrainingBundle\Form\instanceRecordType;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\Common\Collections\ArrayCollection;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FormType;
use Hris\FormBundle\Form\DesignFormType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use DateTime;
use Symfony\Component\HttpFoundation\Response;



/** Report controller.
 *
 * @Route("/trainingsession")
 */
class TraininginstanceController extends Controller
{
    /**
     * Lists all Training entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/", name="trainingsession")
     * @Method("GET|POST")
     * @Template()
     */

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager(); // Get the Entity Manager

        $traininginstance = $em->getRepository('HrisTrainingBundle:Traininginstance')->getAllTrainingInstance(); // Get the repository


        return $this->render('HrisTrainingBundle:Traininginstance:show.html.twig',array(
            'traininginstances'     => $traininginstance
        )); // Render the template us
    }

    /**
     * Lists all Records by forms.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/viewrecords/{formid}/form", requirements={"formid"="\d+"}, defaults={"formid"=0}, name="record_viewrecords")
     * @Method("GET")
     * @Template()
     */
    public function viewRecordsAction($formid)
    {
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($this->getUser());
        $organisationunit = $user->getOrganisationunit();

        if($formid == 0) {
            $formIds = $this->getDoctrine()->getManager()->createQueryBuilder()->select( 'form.id')
                ->from('HrisFormBundle:Form','form')->getQuery()->getArrayResult();
            $formIds = $this->array_value_recursive('id',$formIds);
            $forms = $em->getRepository('HrisFormBundle:Form')->findAll();
        }else {
            $forms = $em->getRepository('HrisFormBundle:Form')->findby(array('id'=>$formid));
            $formIds[]=$formid;
        }

        //Prepare field Option map, converting from stored FieldOption key in record value array to actual text value
        $fieldOptions = $this->getDoctrine()->getManager()->getRepository('HrisFormBundle:FieldOption')->findAll();
        foreach ($fieldOptions as $fieldOptionKey => $fieldOption) {
            $recordFieldOptionKey = ucfirst(Record::getFieldOptionKey());
            $fieldOptionMap[call_user_func_array(array($fieldOption, "get${recordFieldOptionKey}"),array()) ] =   $fieldOption->getValue();
        }

        //If user's organisationunit is data entry level pull only records of his organisationunit
        //else pull lower children too.
        $records = $queryBuilder->select('record')
            ->from('HrisRecordsBundle:Record','record')
            ->join('record.organisationunit','organisationunit')
            ->join('record.form','form');
        if($organisationunit->getOrganisationunitStructure()->getLevel()->getDataentrylevel()) {
            $records = $records
                ->join('organisationunit.organisationunitStructure','organisationunitStructure')
                ->join('organisationunitStructure.level','organisationunitLevel')
                ->andWhere('organisationunitLevel.level >= (
                                        SELECT selectedOrganisationunitLevel.level
                                        FROM HrisOrganisationunitBundle:OrganisationunitStructure selectedOrganisationunitStructure
                                        INNER JOIN selectedOrganisationunitStructure.level selectedOrganisationunitLevel
                                        WHERE selectedOrganisationunitStructure.organisationunit=:selectedOrganisationunit )'
                )
                ->andWhere('organisationunitStructure.level'.$organisationunit->getOrganisationunitStructure()->getLevel()->getLevel().'Organisationunit=:levelId');
            $parameters = array(
                'levelId'=>$organisationunit->getId(),
                'selectedOrganisationunit'=>$organisationunit->getId(),
                'formIds'=>$formIds,
            );
        }else {
            $records = $records->andWhere('organisationunit.id=:selectedOrganisationunit');
            $parameters = array(
                'selectedOrganisationunit'=>$organisationunit->getId(),
                'formIds'=>$formIds,
            );
        }

        $records = $records->andWhere($queryBuilder->expr()->in('form.id',':formIds'))
            ->setParameters($parameters)
            ->getQuery()->getResult();

        $formNames = NULL;
        $visibleFields = Array();
        $formFields = Array();
        $incr=0;
        $formIds = Array();
        foreach($forms as $formKey=>$form) {
            $incr++;
            $formIds[] = $form->getId();
            // Concatenate form Names
            if(empty($formNames)) {
                $formNames = $form->getTitle();
            }else {
                if(count($formNames)==$incr) $formNames.=','.$form->getTitle();
            }
            // Accrue visible fields
            foreach($form->getFormVisibleFields() as $visibleFieldKey=>$visibleField) {

                if(!in_array($visibleField->getField(),$visibleFields)) $visibleFields[] =$visibleField->getField();
            }
            // Accrue form fields
            foreach($form->getFormFieldMember() as $formFieldKey=>$formField) {
                if(!in_array($formField->getField(),$formFields)) $formFields[] =$formField->getField();
            }
        }
        $title = "Employee Records for ".$organisationunit->getLongname();

        $title .= " for ".$formNames;
        if(empty($visibleFields)) $visibleFields=$formFields;

        //getting all User Forms for User Migration

        $user = $this->container->get('security.context')->getToken()->getUser();
        $userForms = $user->getForm();

        $delete_forms = NULL;
        foreach($records as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }

        return array(
            'title'=>$title,
            'visibleFields' => $visibleFields,
            'formFields'=>$formFields,
            'records'=>$records,
            'optionMap'=>$fieldOptionMap,
            'userForms'=>$userForms,
            'delete_forms' => $delete_forms,
        );
    }




    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="trainingsession_update")
     * @Method("PUT")
     *
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Traininginstance')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training session entity.');
        }
        $entity->setCourse($entity->getCourse());
        $entity->setRegion($entity->getRegion());
        $entity->setDistrict($entity->getDistrict());
        $entity->setFacility($entity->getFacility());

        $entity->setStartdate($entity->getStartdate());
        $entity->setEnddate($entity->getEnddate());

        $editForm = $this->createForm(new TraininginstanceType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('trainingsession'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        );
    }


    /**
     * Creates a new Training instance entity.
     *
     * @Route("/create", name="trainingsession_create")
     * @Method("POST")
     *
     */
    public function createAction(Request $request)
    {
        $id = $request->request->get("instance_id");

        $entity  = new Traininginstance();
        $form = $this->createForm(new TraininginstanceType(), $entity);
        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
                $id = $entity->getId();

            if(isset($id)){
                return $this->redirect($this->generateUrl('add_options', array('instance_id' => $id)));
            }else{
                return $this->redirect($this->generateUrl('trainingsession'));
            }

        }

    }




    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/new", name="trainingsession_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new Traininginstance();
        $form = $this->createForm(new TraininginstanceType(), $entity);

        return $this->render('HrisTrainingBundle:Traininginstance:new.html.twig', array(
            'form' => $form->createView(),
        ));



    }

//

    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="trainingsession_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Traininginstance')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training instance entity.');
        }

        $editForm = $this->createForm(new TraininginstanceType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Traininginstance:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form'   => $deleteForm->createView(),

        ));
    }

    /**
     * Returns Fields json.
     *
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/districtFormFields.{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="district_formfields")
     * @Method("POST")
     * @Template()
     */

    public function RequestDropAction(Request $request,$_format)
    {
        $id = $request->request->get("id");
        $em = $this->getDoctrine()->getManager();
        $districts = $em -> getConnection() -> executeQuery('SELECT longname,id FROM hris_organisationunit WHERE hris_organisationunit.parent_id='.$id) -> fetchAll();

        $serializer = $this->container->get('serializer');

        return array(
            'districts' => $serializer->serialize($districts,$_format)
        );
    }

/**
     * Returns Fields json.
     *
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/venueFormFields.{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="venue_formfields")
     * @Method("POST")
     * @Template()
     */

    public function VenueDropAction(Request $request,$_format)
    {
        $id = $request->request->get("id");
        $em = $this->getDoctrine()->getManager();
        $districts = $em -> getConnection() -> executeQuery('SELECT venueName FROM hris_training_venues,hris_organisationunit WHERE hris_training_venues.region = hris_organisationunit.longname AND  hris_organisationunit.id='.$id) -> fetchAll();

        $serializer = $this->container->get('serializer');

        return array(
            'venues' => $serializer->serialize($districts,$_format)
        );
    }



    /**
     * Returns Employee records json.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/records/{_format}/{formid}/{instance_id}/{tabId}", requirements={"_format"="json|"}, defaults={"_format"="json"}, name="records_ajax")
     * @Method("GET")
     * @Template()
     */
    public function ajaxRecordsAction(Request $request,$_format,$formid,$instance_id,$tabId)
    {


        $serializer = $this->container->get('serializer');

        // get query bulder throu doctriine manager
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        // get user org unit
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($this->getUser());
        $organisationunit = $user->getOrganisationunit();

        /// get form by id
        $forms = $em->getRepository('HrisFormBundle:Form')->findby(array('id'=>$formid));
        $formIds[]=$formid;


        //field option

        //


        //If user's organisationunit is data entry level pull only records of his organisationunit
        //else pull lower children too.
        $records = $queryBuilder->select('record.value,record.id')
            ->from('HrisRecordsBundle:Record','record')
            ->join('record.organisationunit','organisationunit')
            ->join('record.form','form');
        if($organisationunit->getOrganisationunitStructure()->getLevel()->getDataentrylevel()) {
            $records = $records
                ->join('organisationunit.organisationunitStructure','organisationunitStructure')
                ->join('organisationunitStructure.level','organisationunitLevel')
                ->andWhere('organisationunitLevel.level >= (
                                        SELECT selectedOrganisationunitLevel.level
                                        FROM HrisOrganisationunitBundle:OrganisationunitStructure selectedOrganisationunitStructure
                                        INNER JOIN selectedOrganisationunitStructure.level selectedOrganisationunitLevel
                                        WHERE selectedOrganisationunitStructure.organisationunit=:selectedOrganisationunit )'
                )
                ->andWhere('organisationunitStructure.level'.$organisationunit->getOrganisationunitStructure()->getLevel()->getLevel().'Organisationunit=:levelId');
            $parameters = array(
                'levelId'=>$organisationunit->getId(),
                'selectedOrganisationunit'=>$organisationunit->getId(),
                'formIds'=>$formIds,
            );
        }else {
            $records = $records->andWhere('organisationunit.id=:selectedOrganisationunit');
            $parameters = array(
                'selectedOrganisationunit'=>$organisationunit->getId(),
                'formIds'=>$formIds,
            );
        }


        // get query bulder throu doctriine manager
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $fieldOption = $queryBuilder->select('fieldOption.uid as fieldOptionUid,fieldOption.value as fieldOptionValue,field.uid as fieldUid,field.caption as fieldCaption,inputType.name as inputTypeName')
            ->from('HrisFormBundle:FieldOption','fieldOption')
            ->join('fieldOption.field','field')
            ->join('field.inputType','inputType')->getQuery()->getResult();


        $records = $records->andWhere($queryBuilder->expr()->in('form.id',':formIds'))
            ->setParameters($parameters)
            ->getQuery()->getResult();

        ///////// getting inserted mapping
        if($tabId=="add_participants"){
            $query = "SELECT * FROM hris_instance_records WHERE hris_instance_records.instance_id =".$instance_id;
            $queryAlready = "SELECT record_id FROM instancefacilitator WHERE instancefacilitator.instance_id =".$instance_id;

        }
        if($tabId=="add_facilitators"){
            $query = "SELECT * FROM instancefacilitator WHERE instancefacilitator.instance_id =".$instance_id;
            $queryAlready = "SELECT record_id FROM hris_instance_records WHERE hris_instance_records.instance_id =".$instance_id;
        }

        $insertedRecords = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $insertedAlready = $em -> getConnection() -> executeQuery($queryAlready) -> fetchAll();

        return array(
            'dataArray'=>$serializer->serialize(array(
                'Records' => $records,
                'fieldOption' => $fieldOption,
                'insertedRecords'=>$insertedRecords,
                'insertedAlready'=>$insertedAlready
            ),$_format)
            );
    }

    /**
     * Returns Employee records json.
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTRECORDS_LIST")
     * @Route("/list_trainers/{_format}/{instance_id}/{tabId}", requirements={"_format"="json|"}, defaults={"_format"="json"}, name="list_trainers")
     * @Method("GET")
     * @Template()
     */
    public function trainersAction(Request $request,$_format,$instance_id,$tabId)
    {

      $serializer = $this->container->get('serializer');
        $em       = $this->getDoctrine()->getManager();
        $trainers = $em->getRepository('HrisTrainingBundle:Trainer')->getAllTrainers();
           $query = "SELECT * FROM instancetrainer WHERE instancetrainer.instance_id =".$instance_id;


        $insertedTrainers = $em -> getConnection() -> executeQuery($query) -> fetchAll();


        return array(
            'dataArray'=>$serializer->serialize(array(
                    'trainers' => $trainers,
                    'insertedTrainers'=>$insertedTrainers
                ),$_format)
        );
    }



    /**
     * @Route("/addparticipants",name="addparticipants")
     * @Method("POST")
     *
     */
    public function addparticipantsAction(Request $request)
    {
        $response = "";
        $ary =  $request->request->get("ary");
        $instance_id = $request->request->get("instance_id");
        $recordIds = explode( ',', $ary );
        try{
            foreach($recordIds as $recordId){
                $instaRec = new instanceRecord();
                $instaRec->setRecordId($recordId);
                $instaRec->setInstanceId($instance_id);
                $em = $this->getDoctrine()->getManager();
                $em->persist($instaRec);
                $em->flush();
            }
            $response = "success";
        }catch(\Exception $e){
            $response = "failure";
        }


        return new Response($response);

    }

    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}/{instance_id}/instanceParticipantdelete", requirements={"id"="\d+"}, name="instanceParticipantdelete")
     * @Method("GET")
     */
    public function instanceRecorddelete(Request $request,$id,$instance_id)
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "DELETE FROM hris_instance_records WHERE hris_instance_records.instance_id = '".$instance_id."' AND hris_instance_records.record_id = '".$id."'";
        $em -> getConnection() -> executeQuery($sql);
        return $this->redirect($this->generateUrl('record_viewparticipants', array('instance_id' => $instance_id)));

    }

    /**
     * @Route("/addfacilitators",name="addfacilitators")
     * @Method("POST")
     *
     */
    public function addfacilitatorsAction(Request $request)
    {
        $response = "success";
        $ary =  $request->request->get("ary");
        $instance_id = $request->request->get("instance_id");
        $recordIds = explode( ',', $ary );
        try{
        foreach($recordIds as $recordId){
            $instaRec = new instanceFacilitator();
            $instaRec->setRecordId($recordId);
            $instaRec->setInstanceId($instance_id);
            $em = $this->getDoctrine()->getManager();
            $em->persist($instaRec);
            $em->flush();
        }
            }catch(\Exception $e){
        $response = "failure";
        }

        return new Response($response);

    }



    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}/{instance_id}/instanceFacilitatordelete", requirements={"id"="\d+"}, name="instanceFacilitatordelete")
     * @Method("GET")
     */
    public function instanceFacilitatorsdelete(Request $request,$id,$instance_id)
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "DELETE FROM instancefacilitator WHERE instancefacilitator.instance_id = '".$instance_id."' AND instancefacilitator.record_id = '".$id."'";
        $em -> getConnection() -> executeQuery($sql);
        return $this->redirect($this->generateUrl('record_viewfacilitators', array('instance_id' => $instance_id)));

    }



    /**
     * @Route("/addtrainers",name="addtrainers")
     * @Method("POST")
     *
     */
    public function addtrainersAction(Request $request)
    {
        $response = "success";
        $ary =  $request->request->get("ary");
        $instance_id = $request->request->get("instance_id");
        $trainerIds = explode( ',', $ary );
        try{
        foreach($trainerIds as $trainerId){
            $instaTra = new instanceTrainer();
            $instaTra->setTrainerId($trainerId);
            $instaTra->setInstanceId($instance_id);
            $em = $this->getDoctrine()->getManager();
            $em->persist($instaTra);
            $em->flush();
        }

        }catch(\Exception $e){
            $response = "failure";
        }

        return new Response($response);

    }


    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}/{instance_id}/instanceTrainersdelete", requirements={"id"="\d+"}, name="instanceTrainers_delete")
     * @Method("DELETE")
     */
    public function instanceTrainersdelete(Request $request,$id,$instance_id)
    {

        $em = $this->getDoctrine()->getManager();
        $sql = "DELETE FROM instanceTrainer WHERE instanceTrainer.instance_id = '".$instance_id."' AND instanceTrainer.trainer_id = '".$id."'";
        $em -> getConnection() -> executeQuery($sql);

        return $this->redirect($this->generateUrl('instanceTrainers', array('instance_id' => $instance_id)));


    }
    /**
     * @Route("/getInstance/{_format}/{instanceId}",requirements={"_format"="json|"}, defaults={"_format"="json"}, name="getInstance")
     * @Method("GET")
     *@Template()
     */
    public function getInstanceAction(Request $request,$_format,$instanceId)
    {
        $serializer = $this->container->get('serializer');
        $em = $this->getDoctrine()->getManager();
        $instance = $em->getRepository('HrisTrainingBundle:Traininginstance')->find($instanceId);
        $instance_name = $instance->getCourse();
        $instane_district = $instance->getDistrict();
        $instane_region = $instance->getRegion();
        $instance_startdate = $instance->getStartdate();

        $trainingInstance = $serializer->serialize(array("instance_name"=>$instance_name,"instance_region"=>$instane_region,"instance_district"=>$instane_district,"instance_startdate"=>$instance_startdate),$_format);
//
        return array(
            "trainingInstance"=>$trainingInstance
        );

    }

    /**
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/addOptions", name="add_options")
     * @Method("GET")
     * @Template()
     */

    public function optionpageAction()
    {



        return array(
            'fieldoption'=>""
        );
    }

    /**
     * Get all values from specific key in a multidimensional array
     *
     * @param $key string
     * @param $arr array
     * @return null|string|array
     */
    public function array_value_recursive($key, array $arr){
        $val = array();
        array_walk_recursive($arr, function($v, $k) use($key, &$val){if($k == $key) array_push($val, $v);});
        return count($val) > 1 ? $val : array_pop($val);
    }


    /**
* Creates a form to delete a Report entity by id.
*
* @param mixed $id The entity id
*
* @return \Symfony\Component\Form\Form The form
*/
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
            ;
    }

}
