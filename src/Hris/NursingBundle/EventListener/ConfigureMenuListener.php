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
 * @author Kelvin Mbwilo <kelvinmbwilo@gmail.com>
 *
 */

namespace Hris\NursingBundle\EventListener;

use Hris\NursingBundle\Event\ConfigureMenuEvent;

class ConfigureMenuListener
{
    /**
     * @param \Hris\NursingBundle\Event\ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        $menu->addChild('Nursing Module', array(
                'uri'=>'#nursingmodule',
                'extras'=>array('tag'=>'div'),
                'name'=>'Nursing Module',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $nursingModule = $menu->getChild('Nursing Module');

        $nursingModule->addChild('Nursing Records Reports',
            array('route'=>'nursing_report',
                'extras'=>array('tag'=>'div'),
                'name'=>'Nursing Records Reports',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $nursingModule->addChild('Nurse Distribution',
            array('route'=>'nursing_distribution_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Nurse Distribution',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $nursingModule->addChild('Superlative Substantive Positions',
            array('route'=>'nursing_substantive_positions_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Superlative Substantive Positions',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $nursingModule->addChild('Special Working Areas',
            array('route'=>'nursing_department_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Special Working Areas',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $nursingModule->addChild('Deceased Nurses Reports',
            array('route'=>'deceased_nursing_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Deceased Nurses Reports',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
        $nursingModule->addChild('Patient/Population Indicator per Nurse',
            array('route'=>'population_distribution_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Population Indicator per Nurse',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
    }
}