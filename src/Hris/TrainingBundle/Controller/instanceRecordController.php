<?php

namespace Hris\TrainingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\TrainingLocation;
use Hris\TrainingBundle\Form\TrainingLocationType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Common\Annotations\True;
use Doctrine\ORM\QueryBuilder as QueryBuilder;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator  as DoctrineHydrator;
use Hris\RecordsBundle\Entity\Record;
use Hris\TrainingBundle\Entity\instanceRecord;
use Hris\RecordsBundle\Form\RecordType;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\Common\Collections\ArrayCollection;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FormType;
use Hris\FormBundle\Form\DesignFormType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * @Route("/InstanceRecord")
 *
 */
class instanceRecordController extends Controller
{
    /**
     * Lists all Training location entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/", name="instanceRecords")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        return array(
                // ...
            );    }

    /**@Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/addrecordInstance",name="addrecord_Instance")
     * @Method("POST")
     * @Template()
     */
    public function addrecordInstanceAction(Request $request)
    {

        $entity  = new instanceRecord();
        $form = $this->createForm(new instanceRecordType(), $entity);
        $form->bind($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

        }

        return array(
            // ...
        );

    }


    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/saverecordInstance", name="participantRecord")
     * @Method("GET|POST")
     * @Template()
     */
    public function saveParticipantsInstanceAction(Request $request)
    {
        $instance_id = $this->getRequest()->get('instance_id');
        $record_id = $this->getRequest()->get('record_id');

        $entity  = new instanceRecord();

        $entity->setInstanceId($instance_id);
        $entity->setRecordId(($record_id));
        $em = $this->getDoctrine()->getManager();

        $record = $em->getRepository('HrisRecordsBundle:Record')->findOneBy(array('id'=>$record_id));
        $record->setHastraining(true);
        $em->persist($record);

        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();


        return new Response($id);
    }

    /**
     * @Route("/editrecordInstance")
     * @Template()
     */
    public function editrecordInstanceAction()
    {
        return array(
                // ...
            );    }

    /**
     * @Route("/deleterecordInstance")
     * @Template()
     */
    public function deleterecordInstanceAction()
    {
        return array(
                // ...
            );    }


    /**
     * Lists all Records by forms.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/viewrecords/{formid}/form", requirements={"formid"="\d+"}, defaults={"formid"=0}, name="instancerecord_viewrecords")
     * @Method("GET")
     * @Template()
     */
    public function viewRecordsAction($formid)
    {
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
            'formid'=>$formid,
        );
    }
}
