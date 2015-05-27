<?php

namespace Hris\TrainingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\EntityRepository;
class SponsorType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('sponsorName')
            ->add('description')
            ->add('phone')
            ->add('email')
            ->add('box')
            ->add('description')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\Sponsor'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_sponsor';
    }
}
