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
namespace Hris\FormBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FieldType;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Field controller.
 *
 * @Route("/field")
 */
class FieldController extends Controller
{

    /**
     * Lists all Field entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_LIST")
     * @Route("/", name="field")
     * @Route("/list", name="field_list")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('HrisFormBundle:Field')->findAll();

        $delete_forms = NULL;
        foreach($entities as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }

        return array(
            'entities' => $entities,
            'delete_forms' => $delete_forms,
        );
    }

    /**
     * Lists all Field API entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_LIST,ROLE_USER")
     * @Route("/api/{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="api_field")
     * @Route("/api/list/{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="api_field_list")
     * @Method("GET")
     * @Template()
     */
    public function indexAPIAction($_format="json")
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $this->getDoctrine()->getManager()->createQuery('SELECT field FROM HrisFormBundle:Field field')->getArrayResult();

        $jsonEntities = json_encode($entities);

        return array(
            'entities' => $jsonEntities,
        );
    }


    /**
     * Creates a new Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_CREATE")
     * @Route("/", name="field_create")
     * @Method("POST")
     * @Template("HrisFormBundle:Field:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Field();
        $form = $this->createForm(new FieldType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('field_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_CREATE")
     * @Route("/new", name="field_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Field();
        $form   = $this->createForm(new FieldType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_SHOW,ROLE_USER")
     * @Route("/api/relatedOptions/{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, requirements={"id"="\w+"}, name="api_field_show")
     * @Method("GET")
     * @Template()
     */
    public function showRelatedOptionsAPIAction()
    {
        $em = $this->getDoctrine()->getManager();

        $selectQuery = 'SELECT  parent_field.uid as parent_fielduid,parent_field.name as parent_fieldname,parent_fieldoption.uid parent_fieldoptionuid,parent_fieldoption.value as parent_fieldoptionvalue,child_field.uid as child_fielduid,child_field.name as child_fieldname,child_fieldoption.uid child_fieldoptionuid,child_fieldoption.value as child_fieldoptionvalue FROM hris_fieldoption_children INNER JOIN hris_fieldoption parent_fieldoption ON parent_fieldoption.id=hris_fieldoption_children.parent_fieldoption INNER JOIN hris_fieldoption child_fieldoption ON child_fieldoption.id=hris_fieldoption_children.child_fieldoption INNER JOIN hris_field parent_field ON parent_field.id=parent_fieldoption.field_id INNER JOIN hris_field child_field ON child_field.id=child_fieldoption.field_id ORDER BY parent_field.name, parent_fieldoption.value, child_field.name, child_fieldoption.value';

        $entityRelations = $this->getDoctrine()->getManager()->getConnection()->fetchAll($selectQuery);

        return array(
            'entities'      => json_encode($entityRelations),
        );
    }

    /**
     * Finds and displays a Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_SHOW")
     * @Route("/{id}", requirements={"id"="\d+"}, name="field_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisFormBundle:Field')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Field entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="field_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisFormBundle:Field')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Field entity.');
        }

        $editForm = $this->createForm(new FieldType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_UPDATE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="field_update")
     * @Method("PUT")
     * @Template("HrisFormBundle:Field:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisFormBundle:Field')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Field entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new FieldType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('field_show', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Field entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_FIELD_DELETE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="field_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisFormBundle:Field')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Field entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('field'));
    }

    /**
     * Creates a form to delete a Field entity by id.
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
