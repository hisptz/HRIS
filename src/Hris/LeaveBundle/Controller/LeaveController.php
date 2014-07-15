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
    public function newAction(  )
    {
        $entity = new LeaveType();
        $form   = $this->createForm(new LeaveTypeType(), $entity);
        return array(
            'form' =>$form->createView(),
            'message'=>''
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

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
//            $entity->setUsername($user->getUsername());
            //Update Record Table hasTraining column
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
                throw $this->createNotFoundException('Unable to find Leave entity.');
            }

            $em->remove($entity);
            $em->flush();
            $em = $this->getDoctrine()->getManager();


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

    /**
     * Lists all Record entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORD_LIST")
     * @Route("/employee_list/{id}", requirements={"id"="\d+"}, name="list_employee")
     * @Method("GET")
     * @Template()
     */
    public function listEmployeeInLeave($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('HrisRecordsBundle:Record')->findAll();
        
        return array(
            'entities' => $entities,
        );
    }

}


