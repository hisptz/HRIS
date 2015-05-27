<?php

namespace Hris\TrainingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use JMS\SecurityExtraBundle\Annotation\Secure;

use Hris\TrainingBundle\Entity\Sponsor;
use Hris\TrainingBundle\Form\SponsorType;
use Hris\RecordsBundle;

class SponsorController extends Controller
{
    /**
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/sponsors", name="sponsors")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager(); // Get the Entity Manager

        $sponsors = $em->getRepository('HrisTrainingBundle:Sponsor')->getAllSponsors(); // Get the repository
        $delete_forms = NULL;
        $AssociateArray = NULL;
        foreach($sponsors as $entity) {
            $delete_form= $this->createDeleteForm($entity->getId());
            $delete_forms[$entity->getId()] = $delete_form->createView();

            $AssociateArray[$entity->getId()] = $this->getSponsorAssociates($entity->getId());
        }
//        print_r($AssociateArray);die();
        return array(
            'sponsors'     => $sponsors,
            'AssociateArray' =>$AssociateArray,
            'delete_forms' =>$delete_forms
        ); // Render the template using necessary parameters

    }

    /**
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_CREATE")
     * @Route("/addSponsor",name="addSponsor")
     * @Method("GET")
     * @Template()
     */
    public function addSponsorAction(Request $request)
    {

        $entity  = new Sponsor();
        $form = $this->createForm(new SponsorType(), $entity);


        return array(
            'form' => $form->createView()
            );    }

    /**
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_CREATE")
     * @Route("/createSponsor",name="createSponsor")
     * @Method("POST")
     */
    public function createSponsorAction(Request $request)
    {
        $entity  = new Sponsor();
        $form = $this->createForm(new SponsorType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('sponsors'));
        }
    }

    /**
    * Displays a form to edit an existing Report entity.
    *
    * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
    * @Route("/{id}/editSponsor", requirements={"id"="\d+"}, name="editSponsor")
    * @Method("GET")
    * @Template()
    */
    public function editSponsorAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $sponsor = $em->getRepository('HrisTrainingBundle:Sponsor')->find($id);

        if (!$sponsor) {
            throw $this->createNotFoundException('Unable to find Sponsor entity.');
        }

        $editForm = $this->createForm(new SponsorType(), $sponsor);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'sponsor'      => $sponsor,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );

    }

    /**
     * Edits an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_UPDATE")
     * @Route("/{id}/updateSponsor", requirements={"id"="\d+"}, name="updateSponsor")
     * @Method("PUT")
     * @Template()
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:Sponsor')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sponsor entity.');
        }
        $entity->setSponsorName($entity->getSponsorName());
        $entity->setDescription($entity->getDescription());
        $entity->setPhone($entity->getPhone());
        $entity->setEmail($entity->getEmail());
        $entity->setBox($entity->getBox());

        $editForm = $this->createForm(new SponsorType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();


        }

        return $this->redirect($this->generateUrl('sponsors'));
    }


    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_DELETE")
     * @Route("/{id}/deleteSponsor", requirements={"id"="\d+"}, name="deleteSponsor")
     * @Method("DELETE")
     */
    public function deleteSponsorAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:Sponsor')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Sponsor entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sponsors'));
    }



    /**
     * Creates a associates of the trainer  by id.
     *
     * @param mixed $id The entity id
     *
     */
    private function getSponsorAssociates($id)
    {
        $associates = "";

        $associateQuery  = "SELECT * FROM hris_training_sponsors S ";
        $associateQuery .= "INNER JOIN hris_traininginstance as T on T.sponsor = S.sponsorname ";
        $associateQuery .= "INNER JOIN hris_trainings as D ON D.id = T.training_id  ";
        $associateQuery .= "WHERE S.id=".$id;

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
