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
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use Hris\OrganisationunitBundle\Entity;

class TrainingLocationType extends AbstractType{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Region','entity', array(
                'class'=>'HrisOrganisationunitBundle:OrganisationUnit',
                'multiple'=>false,
                'required'=>true,
                'property'=>'longname',
                'query_builder'=>function(EntityRepository $er) {
                        return $er->createQueryBuilder('I')
                           ->where('I.parent=1161')
                            ->orderBy('I.longname','ASC');
                    },
                'constraints'=>array(
                    new NotBlank(),
                )
            ))

            ->add('District')
            ->add('facility')
           ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\TrainingLocation'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_traininglocation';
    }
} 