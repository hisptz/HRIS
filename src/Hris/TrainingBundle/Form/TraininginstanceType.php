<?php

namespace Hris\TrainingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\EntityRepository;
class TraininginstanceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder

            ->add('course')

            ->add('region', 'entity', array(
                'class' => 'Hris\OrganisationunitBundle\Entity\Organisationunit',
                'query_builder' => function(EntityRepository $repo){
                        return $repo->createQueryBuilder('q')
                            ->where('q.parent = :parent')
                            ->setParameter('parent',1161);// hard coded, has to be changed as soon as possible

                }))
            ->add('district')
            ->add('venue')
            ->add('sponsor', 'entity', array(
                'class' => 'Hris\TrainingBundle\Entity\Sponsor',
                'query_builder' => function(EntityRepository $repo){
                        return $repo->createQueryBuilder('q');

                    }))
            ->add('startdate','date',array(
                'required' =>True,
                'widget' => 'single_text',
                'format' => 'dd/MM/yy',
                'attr' => array('class' => 'date')
            ))
            ->add('enddate','date',array(
                'required' =>True,
                'widget' => 'single_text',
                'format' => 'dd/MM/yy',
                'attr' => array('class' => 'enddate')
            ))
//            ->add('trainer')

        ;


    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hris\TrainingBundle\Entity\Traininginstance'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hris_trainingbundle_traininginstance';
    }
}
