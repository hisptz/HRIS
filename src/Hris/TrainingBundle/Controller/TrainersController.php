<?php

namespace Hris\TrainingBundle\Controller;

use Hris\TrainingBundle\Entity\instanceTrainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\Trainer;
use Hris\TrainingBundle\Form\TrainerType;
use Hris\TrainingBundle\Form\instanceTrainerType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class TrainersController extends Controller
{
    /**
     * @Route("/trainers", name="trainers")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

 // Get the Entity Manager

        $trainers = $em->getRepository('HrisTrainingBundle:Trainer')->getAllTrainers(); // Get the repository

        $delete_forms=NULL;

        $AssociateArray = Array();
        foreach($trainers as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();

            $AssociateArray[$entity->getId()] = $this->getTrainerAssociates($entity->getId());
        }


        return $this->render('HrisTrainingBundle:Trainers:show.html.twig',array(
            'trainers'     => $trainers,
            'AssociateArray' => $AssociateArray,
            'delete_forms' => $delete_forms
        )); // Render the template using necessary parameters
    }
    /**
     * @Route("/instanceTrainers", name="instanceTrainers")
     * @Method("GET")
     * @Template()
     */
    public function instanceTrainersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /// querying instance record table to see existing record instance pairs//
        $instance_id = $request->query->get('instance_id');// query instance id from route url
        $trainer_ids = array(0=>'');

             $instance_id;
            $query = "SELECT trainer_id FROM hris_instanceTrainer WHERE instance_id = ".$instance_id;
            $trainings= $em -> getConnection() -> executeQuery($query) -> fetchAll();
            $trainersArray = array();
            $i = 0;
            foreach($trainings as $trains ){
                $trainersArray[$i] = $em->getRepository('HrisTrainingBundle:Trainer')->find($trains['trainer_id']); // Get the repository
                $i++;
            }

            $instanceTrainers = $em->getRepository('HrisTrainingBundle:instanceTrainer')->getAllinstanceTrainers(); // Get the repository

            $delete_forms=NULL;
            foreach($instanceTrainers as $entity) {
                $delete_form= $this->createDeleteForm($entity->getId());
                $delete_forms[$entity->getInstanceId()][$entity->getTrainerId()] = $delete_form->createView();
            }

        return array(
            'trainers'     => $trainersArray,
            'delete_forms'     => $delete_forms

        ); // Render the template using necessary parameters
    }


    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="trainers_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Trainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Trainer entity.');
        }

        $editForm = $this->createForm(new TrainerType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Trainers:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_CREATE")
     * @Route("/newTrainer", name="trainers_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new Trainer();
        $form = $this->createForm(new TrainerType(), $entity);

        return $this->render('HrisTrainingBundle:Trainers:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * Displays a form to create a new Report entity.
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
     * @Route("/trainer_details/{id}", requirements={"id"="\d+"}, name="trainer_details")
     * @Method("GET")
     * @Template()
     */
    public function trainerDetailsAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:Trainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Trainer entity.');
        }

        $editForm = $this->createForm(new TrainerType(), $entity);
        $deleteForm = $this->createDeleteForm($id);
        $associates = $this->getTrainerAssociates($id);
        return $this->render('HrisTrainingBundle:Trainers:trainerDetails.html.twig', array(
            'entity'      => $entity,
            'associates'  => $associates,
            'editForm'   => $editForm->createView(),
            'deleteForm' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a new trainers entity.
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_CREATE")
     * @Route("/add_to_instance", name="trainer_add_to_event")
     * @Method("GET|POST")
     *
     */
    public function addToeventAction(Request $request)
    {

        /// querying instance record table to see existing record instance pairs//
        $instance_id = $this->getRequest()->get('instance_id');
        $trainer_id = $this->getRequest()->get('trainer_id');

        $entity  = new instanceTrainer();
//
//
          $entity->setInstanceId($instance_id);
          $entity->setTrainerId($trainer_id);
//
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
//


        return new Response("success");



    }
/**
     * Creates a new trainers entity.
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_CREATE")
     * @Route("/create", name="trainers_create")
     * @Method("POST")
     *
     */
    public function createAction(Request $request)
    {
        $instance_id = $request->query->get('instance_id');// query instance id from route url

        $entity  = new Trainer();
        $form = $this->createForm(new TrainerType(), $entity);
        $form->bind($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            if(isset($instance_id)){
                return $this->redirect($this->generateUrl('trainers', array('instance_id' => $instance_id)));
            }else{
                return $this->redirect($this->generateUrl('trainers'));
            }

        }

    }

    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
     * @Route("/{id}/trainer", requirements={"id"="\d+"}, name="trainers_update")
     * @Method("PUT")
     * @Template("HrisTrainingBundle:Trainers:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Trainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Trainers entity.');
        }


         $entity->setFirstname($entity->getFirstname());
         $entity->setMiddlename($entity->getMiddlename());
         $entity->setLastname($entity->getLastname());
         $entity->setPrimaryJobResponsibility($entity->getPrimaryJobResponsibility());
         $entity->setSecondaryJobResponsibility($entity->getSecondaryJobResponsibility());
         $entity->setEmployer($entity->getEmployer());
         $entity->setPlaceOfWork($entity->getPlaceOfWork());
         $entity->setOrganisationType($entity->getOrganisationType());
         $entity->setTrainerType($entity->getTrainerType());
         $entity->setTrainerLanguage($entity->getTrainerLanguage());
         $entity->setTrainerAffiliation($entity->getTrainerAffiliation());
         $entity->setExperience($entity->getExperience());



        $editForm = $this->createForm(new TrainerType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('trainers'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        );
    }


    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_DELETE")
     * @Route("/{id}/delete", requirements={"id"="\d+"}, name="trainers_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:Trainer')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Trainer entity.');
            }

            $em->remove($entity);
            $em->flush();
            $selectInstanceTrainer = "SELECT *  FROM hris_instanceTrainer WHERE trainer_id =".$id;

            $results = $em -> getConnection() -> executeQuery($selectInstanceTrainer) -> fetchAll();
            foreach($results as $result){
                $entity = $em->getRepository('HrisTrainingBundle:instanceTrainer')->find($result['id']);
                $em->remove($entity);
                $em->flush();
            }

        }

        return $this->redirect($this->generateUrl('trainers'));

    }

    /**
     * Creates a associates of the trainer  by id.
     *
     * @param mixed $id The entity id
     *
     */
    private function getTrainerAssociates($id)
    {
        $associates = "";

        $associateQuery  = "SELECT * FROM hris_instanceTrainer I ";
        $associateQuery .= "INNER JOIN hris_traininginstance as T on T.id = I.instance_id ";
        $associateQuery .= "INNER JOIN hris_trainings as D ON D.id = T.training_id  ";
        $associateQuery .= "WHERE I.trainer_id=".$id;

        $em = $this->getDoctrine()->getManager();
        $associates = $em -> getConnection() -> executeQuery($associateQuery) -> fetchAll();

        return  $associates;
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
