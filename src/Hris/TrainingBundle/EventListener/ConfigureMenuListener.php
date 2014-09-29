<?php
// src/Hris/SampleBundle/EventListener/ConfigureMenuListener.php

namespace Hris\TrainingBundle\EventListener;

use Hris\TrainingBundle\Event\ConfigureMenuEvent;

class ConfigureMenuListener
{
    /**
     * @param \Hris\TrainingBundle\Event\ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        $menu->addChild('Training Module', array(
                'uri'=>'#trainingmodule',
                'extras'=>array('tag'=>'div'),
                'name'=>'Training Module',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $trainingModule = $menu->getChild('Training Module');


        $trainingModule->addChild('Trainings',
            array('route'=>'trainings',
                'extras'=>array('tag'=>'div'),
                'name'=>'Trainings',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $trainingModule->addChild('Training Venues',
            array('route'=>'venues',
                'extras'=>array('tag'=>'div'),
                'name'=>'venues',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $trainingModule->addChild('External Trainers',
            array('route'=>'trainers',
                'extras'=>array('tag'=>'div'),
                'name'=>'Trainers',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $trainingModule->addChild('Sponsors',
            array('route'=>'sponsors',
                'extras'=>array('tag'=>'div'),
                'name'=>'Sponsors',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $trainingModule->addChild('Training Session',
            array('route'=>'trainingsession',
                'extras'=>array('tag'=>'div'),
                'name'=>'Trainingsession',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );
//        $trainingModule->addChild('Internal Trainers/Facilitators',
//                    array('route'=>'facilitator',
//                        'extras'=>array('tag'=>'div'),
//                        'name'=>'facilitator',
//                        'attributes'=> array('class'=>'accordion-group'),
//                    )
//                );

//        $trainingModule->addChild('Participants',
//
//            array('route'=>'record_form_list_instance',
//                'extras'=>array('tag'=>'div'),
//                'name'=>'record_form_list_instance',
//                'attributes'=> array('class'=>'accordion-group'),
//            )
//        );

        $trainingModule->addChild('Training Report',
            array('route'=>'trainingreports',
                'extras'=>array('tag'=>'div'),
                'name'=>'trainingreports',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );
    }
}
