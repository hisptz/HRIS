<?php

namespace Hris\TrainingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\TrainingBundle\Entity\TrainingLocation;
use Hris\TrainingBundle\Form\TrainingLocationType;
use JMS\SecurityExtraBundle\Annotation\Secure;
/** Report controller.
 *
 * @Route("/traininglocation")
 */
class LocationController extends Controller
{
    /**
     * Lists all Training location entities.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_RECORDTRAINING_LIST")
     * @Route("/", name="traininglocation")
     * @Method("GET|POST")
     * @Template()
     */

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager(); // Get the Entity Manager

        $traininglocation = $em->getRepository('HrisTrainingBundle:TrainingLocation')->getAllTrainingLocation(); // Get the repository

        return $this->render('HrisTrainingBundle:Location:show.html.twig',array(
            'traininglocations'     => $traininglocation
        )); // Render the template us
    }

    /**
     * Creates a new Training instance entity.
     *
     * @Route("/create", name="traininglocation_create")
     * @Method("POST")
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new TrainingLocation();
        $form = $this->createForm(new TrainingLocationType(), $entity);
        $form->bind($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('traininglocation'));
        }

    }

    /**
     * Displays a form to create a new Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_CREATE")
     * @Route("/new", name="traininglocation_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {

        $entity  = new TrainingLocation();
        $form = $this->createForm(new TrainingLocationType(), $entity);

        return $this->render('HrisTrainingBundle:Location:new.html.twig', array(
            'form' => $form->createView(),
        ));



    }


    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="traininglocation_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('HrisTrainingBundle:TrainingLocation')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training location instance entity.');
        }

        $editForm = $this->createForm(new TrainingLocationType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('HrisTrainingBundle:Location:edit.html.twig',array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form'   => $deleteForm->createView(),

        ));
    }
    /**
     * Displays a form to edit an existing Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_UPDATE")
     * @Route("/{id}/edit", requirements={"id"="\d+"}, name="traininglocation_update")
     * @Method("PUT")
     * @Template()
     */

        public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $entity = $em->getRepository('HrisTrainingBundle:TrainingLocation')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training Location entity.');
        }
        $entity->setDistrict($entity->getDistrict());
        $entity->setRegion($entity->getRegion());
        $entity->setFacility($entity->getFacility());

        $editForm = $this->createForm(new TrainingLocationType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('traininglocation'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        );
    }
    /**
     * Returns Fields json.
     *
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTHISTORY_GENERATE")
     * @Route("/districtFormFields.{_format}", requirements={"_format"="yml|xml|json"}, defaults={"_format"="json"}, name="district_formfields")
     * @Method("POST")
     * @Template()
     */

        public function RequestDropAction(Request $request,$_format)
    {
        $id = $request->request->get("id");
        $em = $this->getDoctrine()->getManager();
        $districts = $em -> getConnection() -> executeQuery('SELECT longname,id FROM hris_organisationunit WHERE hris_organisationunit.parent_id='.$id) -> fetchAll();

        $serializer = $this->container->get('serializer');

        return array(
            'districts' => $serializer->serialize($districts,$_format)
        );
    }


    /**
     * Deletes a Report entity.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_REPORTSHARING_DELETE")
     * @Route("/{id}", requirements={"id"="\d+"}, name="traininglocation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('HrisTrainingBundle:TrainingLocation')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Training Location entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('traininglocation'));

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
