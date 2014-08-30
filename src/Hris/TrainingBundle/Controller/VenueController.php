<?php

namespace Hris\TrainingBundle\Controller;

use Hris\TrainingBundle\Entity\instanceTrainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\Venue;
use Hris\TrainingBundle\Form\VenueType;
use Hris\TrainingBundle\Form\instanceTrainerType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class VenueController extends Controller
{
    /**
     * @Route("/venues", name="venues")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /// querying instance record table to see existing record instance pairs//
        $instance_id = $request->query->get('instance_id');// query instance id from route url
        $trainer_ids = array(0=>'');
        if(!empty($instance_id)){
            $instance_id;
            $query = "SELECT trainer_id FROM instanceTrainer WHERE instance_id = ".$instance_id;
            $trainings= $em -> getConnection() -> executeQuery($query) -> fetchAll();

            foreach($trainings as $trains ){
                $trainer_ids[] = $trains['trainer_id'];
            }


        }
        // Get the Entity Manager

        $trainers = $em->getRepository('HrisTrainingBundle:Venue')->getAllVenues(); // Get the repository

        $delete_forms=NULL;
        foreach($trainers as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();
        }


        return $this->render('HrisTrainingBundle:Venue:show.html.twig',array(
            'venues'     => $trainers,
            'venues_id' => $trainer_ids,
            'delete_forms' => $delete_forms
        )); // Render the template using necessary parameters
    }

    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/editVenue", requirements={"id"="\d+"}, name="venues_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Venue')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Venue entity.');
        }

        $editForm = $this->createForm(new VenueType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Venue:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/newVenue", name="venues_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new Venue();
        $form = $this->createForm(new VenueType(), $entity);

        return $this->render('HrisTrainingBundle:Venue:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new trainers entity.
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/createVenue", name="venue_create")
     * @Method("POST")
     *
     */
    public function createAction(Request $request)
    {

        $entity  = new Venue();
        $form = $this->createForm(new VenueType(), $entity);
        $form->bind($request);
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

                return $this->redirect($this->generateUrl('venues'));


        }

    }

    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/Venue", requirements={"id"="\d+"}, name="venue_update")
     * @Method("PUT")
     * @Template()
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Venue')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Venue entity.');
        }
        $entity->setVenueName($entity->getVenueName());
        $entity->setRegion($entity->getRegion());
        $entity->setDistrict($entity->getDistrict());

        $editForm = $this->createForm(new VenueType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();


        }

        return $this->redirect($this->generateUrl('venues'));
    }


    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}/deleteVenue", requirements={"id"="\d+"}, name="venues_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:Venue')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Venue entinty.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('venues'));

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
