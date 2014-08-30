<?php
namespace Hris\TrainingBundle\DataFixtures\ORM;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Hris\FormBundle\DataFixtures\ORM\LoadFieldData;
use Hris\FormBundle\DataFixtures\ORM\LoadFormData;
use Hris\FormBundle\DataFixtures\ORM\LoadOrganisationunitData;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Entity\FieldOption;
use Hris\FormBundle\Entity\FormFieldMember;
use Hris\TrainingBundle\Entity\Training;
use Hris\UserBundle\DataFixtures\ORM\LoadUserData;
use Symfony\Component\Stopwatch\Stopwatch;

class LoadTrainingData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * The order in which this fixture will be loaded
     * @return integer
     */
    public function getOrder()
    {
        //LoadIndicator preceeds
        return 1;
        //LoadResourceTable follows
    }

    public function load(ObjectManager $manager)
    {

        $training1 = new Training();
        $training1->setCoursename("Blood Donor Club Training Guide");
        $training1->setCourselocation("Dodoma");
        $training1->setCuriculum("Monitoring and evaluation Book review");
        $training1->setTrainingCategory("Reproductive Child Health");
        $training1->addParticipant("leodard mpande");
        $training1->addParticipant("jacob miller");
        $training1->addTrainer("leonard constantine mpande");
        $training1->setDatecreated(new \DateTime("2011-06-02 18:54:12"));
        $training1->setLastupdated($training1->getDatecreated());
        $manager->persist($training1);

        $training2 = new Training();
        $training2->setCoursename("Blood Receivers Registration Course");
        $training2->setCourselocation("Arusha");
        $training2->setCuriculum("Data management Course");
        $training2->setTrainingCategory("Safe blood management");
        $training2->addParticipant("liziki majuto");
        $training2->addParticipant("Enock karikera");
        $training2->addTrainer("gwen willian stephan");
        $training2->setDatecreated(new \DateTime("2011-06-02 18:54:12"));
        $training2->setLastupdated($training2->getDatecreated());
        $manager->persist($training2);

        $manager->flush();

    }
}