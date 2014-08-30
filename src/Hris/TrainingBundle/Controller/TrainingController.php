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
 *
 */
namespace Hris\TrainingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\Training;
use Hris\TrainingBundle\Form\TrainingType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Hris\RecordsBundle;
/**
 * Report controller.
 *
 * @Route("/trainings")
 */
class TrainingController extends Controller
{


    /**
     * Lists all Training entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/", name="trainings")
     * @Method("GET|POST")
     * @Template()
     */


    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager(); // Get the Entity Manager

        $trainings = $em->getRepository('HrisTrainingBundle:Training')->getAllTrainings(); // Get the repository

        $delete_forms=NULL;
        foreach($trainings as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }

        return $this->render('HrisTrainingBundle:Training:show.html.twig',array(
            'trainings'     => $trainings,
            'delete_forms'     => $delete_forms
        )); // Render the template using necessary parameters

    }
    /**
     * Creates a new Training entity.
     *
     * @Route("/create", name="trainings_create")
     * @Method("POST")
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Training();
        $form = $this->createForm(new TrainingType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('trainings'));
        }

    }

    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/new", name="trainings_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new Training();
        $form = $this->createForm(new TrainingType(), $entity);

        return $this->render('HrisTrainingBundle:Training:new.html.twig', array(
            'form' => $form->createView(),
        ));



    }
//
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
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="trainings_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Training')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Trainings entity.');
        }

        $editForm = $this->createForm(new TrainingType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Training:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
//
    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/trainings_update", requirements={"id"="\d+"}, name="trainings_update")
     * @Method("PUT")
     * @Template()
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Training')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Trainings entity.');
        }
        $entity->setCoursename($entity->getCoursename());
        $entity->setTrainingCategory($entity->getTrainingCategory());
        $entity->setTrainingInstruction($entity->getTrainingInstruction());
        $entity->setSponsor($entity->getSponsor());
        $entity->setCuriculum($entity->getCuriculum());

        $editForm = $this->createForm(new TrainingType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('trainings'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        );
    }
    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}/training_delete", requirements={"id"="\d+"}, name="trainings_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:Training')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Training entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('trainings'));

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
