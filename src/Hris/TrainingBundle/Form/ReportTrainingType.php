<?php
/*
 *
 * Copyright 2012 Human Resource Information System
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @since 2012
 * @author John Francis Mukulu <john.f.mukulu@gmail.com>
 * @author Ismail Yusuf Koleleni <ismailkoleleni@gmail.com>
 *
 */
namespace Hris\TrainingBundle\Form;

use Hris\ReportsBundle\Form\OrganisationunitToIdTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportTrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // assuming $entityManager is passed in options
        $em = $options['em'];
        $username = $this->getUsername();
        $transformer = new OrganisationunitToIdTransformer($em);
        $builder
            ->add($builder->create('organisationunit','hidden',array(
                    'required'=>True,
                    'constraints'=> array(
                        new NotBlank(),
                    )
                ))->addModelTransformer($transformer)
            )
            ->add('withLowerLevels','checkbox',array(
                'required'=>True,
            ))
            ->add('withLowerLevels','checkbox',array(
                'required'=>True,
            ))
            ->add('reportType','choice',array(
                'choices'=>array(
                    '' => '--SELECT--',
                   'trainings'=>'Trainings Report',
                    'facilitators'=>'Training Facilitators Report',
                    'participants'=>'Training Participants Report',
//                  'externals'=>'External Trainers Training Report'
                ),
                'required'=>True,
                'constraints'=>array(
                    new NotBlank(),
                )
            ))
            ->add('Trainings','entity', array(
                'class'=>'HrisTrainingBundle:Training',
                'multiple'=>true,
                'required'=>false,
                'property'=>'coursename',
                'constraints'=>array(
                    new NotBlank(),
                )
            ))
            ->add('forms','entity', array(
                'class'=>'HrisFormBundle:Form',
                'multiple'=>true,
                'required'=>true,
                'query_builder'=>function(EntityRepository $er) use ($username) {
                        return $er->createQueryBuilder('form')
                            ->join('form.user','user')
                            ->andWhere("user.username='".$username."'")
                            ->orderBy('form.name','ASC');
                    },
                'constraints'=>array(
                    new NotBlank(),
                )
            ))

            ->add('graphType','choice',array(
                'choices'=>array(
                    'bar'=>'Bar Chart',
                    'line'=>'Line Chart',
                    'pie'=>'Pie Chart'
                ),
                'constraints'=>array(
                    new NotBlank(),
                )
            ))
//            ->add('ProduceByDateRange','checkbox',array(
//                'required'=>False,
//            ))
//            ->add('startdate','date',array(
//                'required'=>false,
//                'widget' => 'single_text',
//                'format' => 'dd/MM/yyyy',
//                'attr' => array('class' => 'date')
//            ))
//            ->add('enddate','date',array(
//                'required'=>false,
//                'widget' => 'single_text',
//                'format' => 'dd/MM/yyyy',
//                'attr' => array('class' => 'date')
//            ))
            ->add('Generate Report','submit',array(
                'attr' => array('class' => 'btn'),
            ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array('em')
        );
        $resolver->setAllowedTypes(array(
            'em'=>'Doctrine\Common\Persistence\ObjectManager',
        ));
    }

    public function getName()
    {
        return 'hris_reportsbundle_reporthistorytrainingtype';
    }

    /**
     * @param $username
     */
    public function __construct ($username)
    {
        $this->username = $username;
    }

    /**
     * @var string
     */
    private $username;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
}
