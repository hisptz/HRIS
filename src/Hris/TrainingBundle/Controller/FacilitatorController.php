<?php

namespace Hris\TrainingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hris\TrainingBundle\Entity\instanceFacilitator;
use Hris\TrainingBundle\Entity\instanceRecord;
use Hris\TrainingBundle\Form\instanceRecordType;
use Hris\RecordsBundle\Entity\Record;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Hris\RecordsBundle;

/** Report controller.
 *
 * @Route("/facilitator")
 */
class FacilitatorController extends Controller
{

    /**
     * Lists all Facilitator entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/", name="facilitator")
     * @Method("GET|POST")
     * @Template()
     */

    public function indexAction()
    {
        return array(
                // ...
            );    }

    /**
     * Lists all Facilitator entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/addFacilitator", name="addFacilitator")
     * @Method("GET|POST")
     * @Template()
     */
    public function addFacilitatorAction(Request $request)
    {
        $instance_id = $this->getRequest()->get('instance_id');
        $record_id = $this->getRequest()->get('record_id');



        $entity  = new instanceFacilitator();


            $entity->setInstanceId($instance_id);
            $entity->setRecordId(($record_id));
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $id = $entity->getId();


        return new Response($id);
    }


    /**
     * Lists all Records by forms.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/viewrecords/", name="record_viewfacilitators")
     * @Method("GET")
     * @Template()
     */
    public function viewFacilitatorsAction(Request $request)
    {
        /// querying instance record table to see existing record instance pairs//
        $instance_id = $request->query->get('instance_id');// query instance id from route url

        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($this->getUser());
        $organisationunit = $user->getOrganisationunit();


            $formIds = $this->getDoctrine()->getManager()->createQueryBuilder()->select( 'form.id')
                ->from('HrisFormBundle:Form','form')->getQuery()->getArrayResult();
            $forms = $em->getRepository('HrisFormBundle:Form')->findAll();

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

        $title = "Internal Trainers/Facilitators of ".$training['coursename']."  of ".$training['district']." (".$training['startdate'].")   To Employee Records for ".$organisationunit->getLongname();

//        $title .= " for ".$formNames;
        if(empty($visibleFields)) $visibleFields=$formFields;

        //getting all User Forms for User Migration

        $user = $this->container->get('security.context')->getToken()->getUser();
        $userForms = $user->getForm();


        $query = "SELECT record_id FROM hris_instanceFacilitator WHERE instance_id =".$instance_id;
        $record_ids = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $id_record = array(0=>-1);
        $i = 0;
        foreach($record_ids as $records_id ){
            $id_record[] = $records_id['record_id'];
        }

        $instanceFacilitators = $em->getRepository('HrisTrainingBundle:instanceFacilitator')->getAllFacilitators(); // Get the repository

        $delete_forms=NULL;
        foreach($instanceFacilitators as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getInstanceId()][$entity->getRecordId()] = $delete_form->createView();
        }

        return array(
            'title'=>$title,
            'visibleFields' => $visibleFields,
            'formFields'=>$formFields,
            'records'=>$records,
            'optionMap'=>$fieldOptionMap,
            'userForms'=>$userForms,
            'formid'=>'',
            'delete_forms'=>$delete_forms,
            'instanceFacilitators'=>$instanceFacilitators,
            'record_ids'=>$id_record

        );
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
