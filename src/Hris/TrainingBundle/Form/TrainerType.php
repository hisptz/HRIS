<?php
/**
 * Created by PhpStorm.
 * User: hrhis
 * Date: 7/3/14
 * Time: 6:27 PM
 */

namespace Hris\TrainingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class TrainerType extends AbstractType{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('firstname')
                ->add('middlename')
                ->add('lastname')
                ->add('primaryJobResponsibility')
                ->add('secondaryJobResponsibility')
                ->add('profession')
                ->add('employer')
                ->add('placeOfWork')
                ->add('highestLevelOfQualification','choice', array(
                'choices' => array('PHD' => 'PHD', 'Masters' => 'Masters','Bachelor Degree'=>'Bachelor Degree','Other'=>'Other')
            ))
                ->add('organisationType')
                ->add('trainerType','choice', array(
                    'choices' => array('Consultant' => 'Consultant', 'TOT' => 'Trainer Of Trainers(TOT)','Support'=>'Support')
                ))
                ->add('trainerLanguage','choice', array(
                'choices' => array('English' => 'English', 'Swahili' => 'Swahili','Other'=>'Other')
            ))
                ->add('trainerAffiliation')
                ->add('experience');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\Trainer'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_trainer';
    }
} 