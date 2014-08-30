<?php

namespace Hris\TrainingBundle\Controller;

use Hris\TrainingBundle\Entity\instanceRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Hris\TrainingBundle\Entity\Participant;
use Hris\TrainingBundle\Form\ParticipantType;
use Hris\TrainingBundle\Form\instanceRecordType;
use Hris\TrainingBundle\Entity\Traininginstance;
use Hris\TrainingBundle\Form\TraininginstanceType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;



use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Common\Annotations\True;
use Doctrine\ORM\QueryBuilder as QueryBuilder;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator  as DoctrineHydrator;
use Hris\RecordsBundle\Entity\Record;
use Hris\RecordsBundle\Form\RecordType;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\Common\Collections\ArrayCollection;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FormType;
use Hris\FormBundle\Form\DesignFormType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

use DateTime;



/**
 * @Route("/participants")
 *
 */
class ParticipantController extends Controller
{


    /**
     * Lists all Participants entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/", name="participants")
     * @Method("GET|POST")
     * @Template()
     */


    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager(); // Get the Entity Manager

        $participants = $em->getRepository('HrisTrainingBundle:Participant')->getAllParticipants(); // Get the repository


        return $this->render('HrisTrainingBundle:Participant:show.html.twig',array(
            'participants'     => $participants
        )); // Render the template using necessary parameters
//
    }


    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/new", name="participants_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new Participant();
        $form = $this->createForm(new ParticipantType(), $entity);
        return $this->render('HrisTrainingBundle:Participant:new.html.twig', array(
            'form' => $form->createView(),
        ));



    }
 /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/{id}/newInstance", requirements={"id"="\d+"}, name="newInstance")
     * @Method("GET")
     * @Template()
     */
    public function addInstanceAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        if(!empty($id)){

//            $report = $em -> getConnection() -> executeQuery($query) -> fetchAll();
            $record = $em->getRepository('HrisRecordsBundle:Record')->findOneBy(array('id'=>$id));


            $entities = $record->getInstances();
            var_dump($entities);
            die();
            $entities = $em->getRepository('HrisTrainingBundle:Traininginstance')->findBy(array('record'=>$record));
            $employeeName = $this->getEmployeeName($id);
        }
        $delete_forms = array();
        foreach($entities as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }

        return $this->render('HrisTrainingBundle:Participant:addInstance.html.twig', array(
            'entities' => $entities,
            'id' => $id,
            'delete_forms' => $delete_forms,
            'recordid' => $id,
            'record' => $record,
            'employeeName'=>$employeeName,
        ));

    }
/**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/newInstanceRecord",name="saveInstance")
     * @Method("POST")
     */
    public function saveInstanceAction(Request $request)
    {

        $entity  = new instanceRecord();
        $form = $this->createForm(new instanceRecordType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $response = "success";
        }else{
            $response = "failure";
        }


   return new Response($response);

    }

/**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/{recordid}/new_trainingevent", requirements={"recordid"="\d+"}, name="new_userinstance")
     * @Method("POST|GET")
     * @Template()
     */
    public function newInstanceAction(Request $request, $recordid)
    {
         $employeeName = $this->getEmployeeName($recordid);


        $defaultData = array('message' => 'Assign User  Training Instance');
        $form = $this->createFormBuilder($defaultData)
            ->add('Traininginstance', 'entity', array(
                'class' => 'HrisTrainingBundle:Traininginstance',
                'query_builder' => function($repository) { return $repository->createQueryBuilder('p')->orderBy('p.id', 'ASC'); },
                'property' => 'coursename',
                'data' => 'id',
            ))
            ->getForm();

        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {
                $postData = current($request->request->all());

                $id       = $postData["Traininginstance"];
            $em = $this->getDoctrine()->getManager();
            $instance = $em->getRepository('HrisTrainingBundle:Traininginstance')->find($id);

            if (!$instance) {
                throw $this->createNotFoundException(
                    'No Training Event Found found for id '.$id
                );
            }
                $recordInstance = $em->getRepository('HrisRecordsBundle:Record')->find($recordid);
                $instance->setRecord($recordInstance);
                $em->flush();

                return $this->redirect($this->generateUrl('newInstance',array('id'=>$recordid)));

            }


      }

        return $this->render('HrisTrainingBundle:Participant:newInstance.html.twig', array(
//            'entities' => $entities,
            'record_id' => $recordid,
            'form' => $form->createView(),
            'employeeName'=>$employeeName,
        ));
    }

    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/{id}/remove_event", requirements={"id"="\d+"}, name="training_instance_delete")
     * @Method("GET")
     * @Template()
     */
    public function deleteInstanceAction($rid)
    {
//
//
    }

    /**
     * Lists all Records by forms.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/viewrecords/{formid}/form", requirements={"formid"="\d+"}, defaults={"formid"=0}, name="record_viewinstance")
     * @Method("GET")
     * @Template()
     */
    public function trainingInstanceAction(Request $request,$formid)
    {
        /// querying instance record table to see existing record instance pairs//
        $instance_id = $request->query->get('instance_id');// query instance id from route url
        $id = $request->query->get('id');// query instance id from route url

        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $userManager = $this->get('fos_user.user_manager');
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

        $title_query = "select *  from hris_trainings T,hris_traininginstance I where T.id = I.training_id and I.id=".$instance_id;

        $trainings= $em -> getConnection() -> executeQuery($title_query) -> fetchAll();
        $training = array(0=>'');
        foreach($trainings as $trains ){
            $training['coursename'] = $trains['coursename'];
            $training['district'] = $trains['district'];
            $training['startdate'] = $trains['startdate'];
            $training['facility'] = $trains['venue'];
        }
        if($id=="partic_for_view"){
            $id = "Participants";
        }elseif($id =="facilitator"){
            $id ="Facilitators";
        }else{
            $id = "Add Participants or Facilitators";
        }

        $startdate = strtotime($training['startdate']);

        $startdate = date('d-m-Y', $startdate);


        $title = $id." for ".$training['coursename']."  of ".$training['district']." of date " .$startdate."  To Employee Records for ".$organisationunit->getLongname();

        $title .= " for ".$formNames;
        if(empty($visibleFields)) $visibleFields=$formFields;

        //getting all User Forms for User Migration

        $user = $this->container->get('security.context')->getToken()->getUser();
        $userForms = $user->getForm();
        $instance = new instanceRecord();
          $instanceRecordForm = $this->createForm(new instanceRecordType($this->getUser()),$instance,array('method'=>'POST'));
          $instanceRecordForm = $instanceRecordForm->createView();


        $query = "SELECT record_id FROM hris_instance_records WHERE instance_id =".$instance_id;
        $record_ids = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $id_record = array(0=>-1);
        foreach($record_ids as $records_id ){
            $id_record[] = $records_id['record_id'];
        }

        $query = "SELECT record_id FROM instanceFacilitator WHERE instance_id =".$instance_id;
        $facilitator_record_ids = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $facilitator_ids = array(0=>-1);
        foreach( $facilitator_record_ids as $records_id ){
            $facilitator_ids[] = $records_id['record_id'];
        }

        return array(
        'title'=>$title,
        'visibleFields' => $visibleFields,
        'formFields'=>$formFields,
        'records'=>$records,
        'optionMap'=>$fieldOptionMap,
        'userForms'=>$userForms,
        'formid'=>$formid,
        'record_ids'=>$id_record,
        'facilitator_ids'=>$facilitator_ids,
        'instanceRecordForm'=>$instanceRecordForm
    );

    }

/**
     * Lists all Records by forms.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/viewrecords/", name="record_viewparticipants")
     * @Method("GET")
     * @Template()
     */
    public function participantsForviewOnlyAction(Request $request)
    {
        /// querying instance record table to see existing record instance pairs//
        $instance_id = $request->query->get('instance_id');// query instance id from route url
        $id = $request->query->get('id');// query instance id from route url

        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($this->getUser());
        $organisationunit = $user->getOrganisationunit();

//        if($formid == 0) {
            $formIds = $this->getDoctrine()->getManager()->createQueryBuilder()->select( 'form.id')
                ->from('HrisFormBundle:Form','form')->getQuery()->getArrayResult();
//            $formIds = $this->array_value_recursive('id',$formIds);
            $forms = $em->getRepository('HrisFormBundle:Form')->findAll();
//        }else {
//            $forms = $em->getRepository('HrisFormBundle:Form')->findby(array('id'=>$formid));
//            $formIds[]=$formid;
//        }

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

        $title_query = "select *  from hris_trainings T,hris_traininginstance I where T.id = I.training_id and I.id=".$instance_id;

        $trainings= $em -> getConnection() -> executeQuery($title_query) -> fetchAll();
        $training = array(0=>'');
        foreach($trainings as $trains ){
            $training['coursename'] = $trains['coursename'];
            $training['district'] = $trains['district'];
            $training['startdate'] = $trains['startdate'];
            $training['venue'] = $trains['venue'];
        }


        $startdate = strtotime($training['startdate']);

        $startdate = date('d-m-Y', $startdate);


        $title = "Participants for ".$training['coursename']."  of ".$training['district']." of date ".$startdate."   To Employee Records for ".$organisationunit->getLongname();

        $title .= " for ".$formNames;
        if(empty($visibleFields)) $visibleFields=$formFields;

        //getting all User Forms for User Migration

        $user = $this->container->get('security.context')->getToken()->getUser();
        $userForms = $user->getForm();
        $instance = new instanceRecord();
          $instanceRecordForm = $this->createForm(new instanceRecordType($this->getUser()),$instance,array('method'=>'POST'));
          $instanceRecordForm = $instanceRecordForm->createView();


        $query = "SELECT record_id FROM hris_instance_records WHERE instance_id =".$instance_id;
        $record_ids = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $id_record = array(0=>-1);
        foreach($record_ids as $records_id ){
            $id_record[] = $records_id['record_id'];
        }

        return $this->render('HrisTrainingBundle:Participant:participantsForviewOnly.html.twig',array(
            'title'=>$title,
            'visibleFields' => $visibleFields,
            'formFields'=>$formFields,
            'records'=>$records,
            'optionMap'=>$fieldOptionMap,
            'userForms'=>$userForms,
            'formid'=>"",
            'record_ids'=>$id_record,
            'instanceRecordForm'=>$instanceRecordForm
        ));
    }


//    /**
//     * Returns Employee's Full Name
//     *
//     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDHISTORY_SHOWEMPLOYEENAME")
//     * @Method("GET")
//     * @Template()
//     */
    private function getEmployeeName($recordid)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if(!empty($recordid)) {
            $record = $this->getDoctrine()->getManager()->getRepository('HrisRecordsBundle:Record')->findOneBy(array('id'=>$recordid));
        }else {
            $record = NULL;
        }
        $resourceTableName = "_resource_all_fields";
        $query = "SELECT firstname, middlename, surname FROM ".$resourceTableName;
        $query .= " WHERE instance = '".$record->getInstance()."' ";

        $result = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        if(!empty($result)){
            return $result[0]['firstname']." ".$result[0]['middlename']." ".$result[0]['surname'];
        }else{
            return "Employee";
        }

    }




    /* @Route("/{recordid}/traininginstance", requirements={"recordid"="\d+"}, name="traininginstance_byrecord")
     * @Route("/list/{recordid}/", requirements={"recordid"="\d+"}, name="traininginstance_list_byrecord")
     * @Method("GET")
     * @Template()
     */
    public function instanceByRecordsAction( $recordid=NULL )
    {
        $em = $this->getDoctrine()->getManager();

        if(!empty($recordid)){
            $entities = $em->getRepository('HrisRecordsBundle:Training')->findBy(array('record'=>$recordid));
            $record = $em->getRepository('HrisRecordsBundle:Record')->findOneBy(array('id'=>$recordid));
        }

        $delete_forms = array();
        foreach($entities as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }


        return array(
            'entities' => $entities,
            'delete_forms' => $delete_forms,
            'recordid' => $recordid,
            'record' => $record,
            'employeeName' => $this->getEmployeeName($recordid),
        );
    }



    /**
 * Displays a form to create a new Report entity.
 *
 * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
 * @Route("/create", name="participants_create")
 * @Method("POST")
 * @Template()
 */
    public function createAction(Request $request)
    {
        $entity  = new Participant();
        $form = $this->createForm(new ParticipantType(), $entity);
        $form->bind($request);
        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($form->isValid()) {
            $entity->setUsername($user->getUsername());
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('participants'));
        }

    }


    /**
     * Finds and displays a Report entity.
     *
     *
     * @Route("/",name="trainings_show")
     * @Method("GET|POST")
     * @Template()
     */
    public function showAction()
    {

    }

    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="participants_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Participant')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Participant entity.');
        }

        $editForm = $this->createForm(new ParticipantType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Participant:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form'   => $deleteForm->createView(),

        ));
    }

    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/update", requirements={"id"="\d+"}, name="participants_update")
     * @Method("PUT")
     * //@Template("HrisTrainingBundle:Participant:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Participant')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Participant entity.');
        }
        $entity->setUid($user->getId());
        $entity->setUsername($user->getUsername());
        $entity->setFirstname($entity->getFirstname());
        $entity->setMiddlename($entity->getMiddlename());
        $entity->setLastname($entity->getLastname());
        $entity->setCurrentJobResponsibility($entity->getCurrentJobResponsibility());
        $entity->setCurrentJobTitle($entity->getCurrentJobTitle());
        $entity->setTown($entity->getTown());
        $entity->setDistrict($entity->getDistrict());
        $entity->setRegion($entity->getRegion());

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ParticipantType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('participants'));//, array( 'recordid' => $entity->getRecord()->getId() )));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="participants_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:Participant')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Participant entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('participants'));

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
