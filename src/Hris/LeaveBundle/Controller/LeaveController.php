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
 * @author Kelvin Mbwilo <kelvinmbwilo@gmail.com>
 *
 */
namespace Hris\LeaveBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Tests\Common\Annotations\True;
use Hris\FormBundle\Entity\FieldOption;
use Hris\LeaveBundle\Form\LeaveTypeType;
use Hris\RecordsBundle\Entity\History;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\QueryBuilder as QueryBuilder;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator  as DoctrineHydrator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\RecordsBundle\Entity\Record;
use Hris\LeaveBundle\Entity\LeaveType;
use Hris\RecordsBundle\Form\RecordType;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\Common\Collections\ArrayCollection;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FormType;
use Hris\FormBundle\Form\DesignFormType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use JMS\SecurityExtraBundle\Annotation\Secure;
use DateTime;

/**
 * Record controller.
 *
 * @Route("/leave")
 */
class LeaveController extends Controller
{

    /**
     * Lists all Record entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/", name="leave")
     * @Route("/list", name="list_leave")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $initial_leaves = $em -> getConnection() -> executeQuery(
            "SELECT * FROM hris_fieldoption WHERE field_id=136 AND hastraining IS NULL"
//            "SELECT * FROM hris_fieldoption WHERE field_id=136 AND hastraining=TRUE"
        ) -> fetchAll();
        $inleaves = array();
//        foreach($initial_leaves as $leave){
//            $leaveTypes = $em -> getConnection() -> executeQuery(
//                "SELECT * FROM hris_leave_type"
//            ) -> fetchAll();
//            $leaves = array();
//            foreach($leaveTypes as $leave1){
//                $leaves[] = $leave1['field_id'];
//            }
////            var_dump($leaves);exit;
//            if(in_array($leave['id'],$leaves)){
//                $inleaves[] = $leave['value'];
//            }else{
//                $date = date("Y-m-d H:i:s");
//                $id = count($leaves)+1;
//                $em -> getConnection() -> executeQuery(
//                    "INSERT INTO hris_leave_type VALUES($id,{$leave['id']},'{$leave['value']}','{$leave['uid']}',0,'Not Applicable','{$leave['value']}','Not Applicable','{$date}','{$date}')"
//                );
//            }
//
//
//        }

        $entities = $em->getRepository('HrisLeaveBundle:LeaveType')->findAll();
        $delete_forms = array();
        foreach($entities as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }
        return $this->render('HrisLeaveBundle:Leave:index.html.twig',array(
            'entities'=>$entities,
            'delete_forms' => $delete_forms,
        ));
    }

    /**
     * Displays a form to create a new Record entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_CREATE")
     * @Route("/new", name="new_leave")
     * @Method("GET")
     * @Template()
     */
    public function newAction( )
    {
        $entity = new LeaveType();

        $message = $this->getRequest()->get('message');

        $form   = $this->createForm(new LeaveTypeType(), $entity);
        return array(
            'form' =>$form->createView(),
            'message'=>$message
        );
    }

    /**
     * Creates a new Leave entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_CREATE")
     * @Route("/addleave", name="leave_create")
     * @Method("POST")
     * @Template("HrisLeaveBundle:Leave:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new LeaveType();
        $form = $this->createForm(new LeaveTypeType(), $entity);
        $form->bind($request);
        $user = $this->container->get('security.context')->getToken()->getUser();

        $field = $this->getDoctrine()->getManager()->getRepository('HrisFormBundle:Field')->findOneBy(array('id'=>136));

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $fieldoption  = new FieldOption();
            $formData = $form->getData();
            $fieldoption->setDescription($formData->getDescription());
            $fieldoption->setField($field);
            $fieldoption->setHasTraining(True);
            $fieldoption->setValue($formData->getName());
            $entity->setRecord($fieldoption);
            $em->persist($fieldoption);
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('new_leave', array( 'message' => 'Added Successful' )));
        }

        return array(
            'message' => '',
            'form'   => $form->createView(),
        );
    }

    /**
     * Deletes a Leave entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_DELETE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="leave_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisLeaveBundle:LeaveType')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find FieldOption entity.');
            }
            $em->remove($entity);
            $em->flush();



            return $this->redirect($this->generateUrl('leave'));
        }


       }

    /**
     * Creates a form to delete a Leave entity by id.
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

    /**
     * Displays a form to edit an existing Leave entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDHISTORY_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="leave_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisLeaveBundle:LeaveType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Leave entity.');
        }

        $editForm = $this->createForm(new LeaveTypeType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }


    /**
     * Edits an existing Leave entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="leave_update")
     * @Method("PUT")
     * @Template("HrisLeaveBundle:Leave:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisLeaveBundle:LeaveType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Leave entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new LeaveTypeType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('leave'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }


    public function listEmployeeInLeave($leave,$id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";
        $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
        $query .= "FROM hris_record_history H ";
        $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
        $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
        $query .= " WHERE H.history = ". $leave;
         $query .= " ORDER BY R.firstname ASC";
        //get the records
        var_dump($query);
        die();
        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();

        return array(
            'entities' => $report,
        );
    }

    /**
     * Lists all Record entities within a single leave.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/employeeList/", name="list_employee_on_leave")
     * @Method("GET")
     * @Template()
     */

    public function employeeInLeaveAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $resourceTableName = "_resource_all_fields";
        $query = "SELECT R.firstname, R.middlename, R.surname, R.profession, H.history, H.reason, H.record_id, H.entitled_payment, H.startdate, H.enddate, H.entitled_payment, R.level5_facility ";
        $query .= "FROM hris_record_history H ";
        $query .= "INNER JOIN hris_record as V on V.id = H.record_id ";
        $query .= "INNER JOIN ".$resourceTableName." as R on R.instance = V.instance ";
        $query .= " WHERE H.history = '". $request->query->get('leave')."'";
         $query .= " ORDER BY H.startdate DESC";
        //get the records

        $report = $entityManager -> getConnection() -> executeQuery($query) -> fetchAll();
        return array(
            'entities' => $report,
            'leave'    => $request->query->get('leave'),
        );
    }

}


