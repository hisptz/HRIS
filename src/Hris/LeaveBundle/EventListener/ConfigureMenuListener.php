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

namespace Hris\LeaveBundle\EventListener;

use Hris\LeaveBundle\Event\ConfigureMenuEvent;

class ConfigureMenuListener
{
    /**
     * @param \Hris\LeaveBundle\Event\ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        $menu->addChild('Leave Module', array(
                'uri'=>'#leavemodule',
                'extras'=>array('tag'=>'div'),
                'name'=>'Leave Module',
                'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $leaveModule = $menu->getChild('Leave Module');

        $leaveModule->addChild('Leave Settings',
            array('route'=>'leave',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Leave Settings',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $leaveModule->addChild('Leave Management',
            array('route'=>'record_form_list_leaverecords',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Leave Management',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );

        $leaveModule->addChild('Leave Reports',
            array('route'=>'leave_report',
                  'extras'=>array('tag'=>'div'),
                  'name'=>'Leave Reports',
                  'attributes'=> array('class'=>'accordion-group'),
            )
        );
    }
}