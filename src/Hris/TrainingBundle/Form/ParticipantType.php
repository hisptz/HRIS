<?php

namespace Hris\TrainingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ParticipantType extends AbstractType
{
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
            ->add('currentJobResponsibility')
            ->add('currentJobTitle')
            ->add('qualificationAndEmployement')
            ->add('region')
            ->add('district')
            ->add('town')

        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\Participant'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_participant';
    }
}
