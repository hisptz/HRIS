<?php

namespace Hris\LeaveBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LeaveTypeType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',array(
                'required'=>True,
            ))
            ->add('uid','hidden')
            ->add('maximumDays','integer',array(
                'required'=>false,
            ))
            ->add('limitApplicable', 'choice', array(
                'choices'   => array(''=>'Select','Applicable' => 'Applicable', 'Not Applicable' => 'Not Applicable'),
                'required'  => true,
            ))
            ->add('description','textarea', array(
                'required'=>false,
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\LeaveBundle\Entity\LeaveType'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_leavebundle_leavetype';
    }
}