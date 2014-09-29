<?php

namespace Hris\TrainingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class instanceFacilitatorType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uid','hidden')
            ->add('instanceId','hidden')
            ->add('recordId','hidden')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\instanceFacilitator'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_instancefacilitator';
    }
}
