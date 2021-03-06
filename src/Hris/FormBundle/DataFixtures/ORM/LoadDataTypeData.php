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
 *
 */
namespace Hris\FormBundle\DataFixtures\ORM;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Hris\FormBundle\Entity\DataType;
use Symfony\Component\Stopwatch\Stopwatch;

class LoadDataTypeData extends AbstractFixture implements OrderedFixtureInterface
{
	/**
	 * {@inheritDoc}
	 * @see Doctrine\Common\DataFixtures.FixtureInterface::load()
	 */
	public function load(ObjectManager $manager)
	{
        $stopwatch = new Stopwatch();
        $stopwatch->start('dummyDataTypesGeneration');

		// Load Public Data
		$dataTypeNames = Array('String','Integer','Double','Date','Telephone','Email');
		foreach($dataTypeNames as $key=>$dataTypeName) {
			$dataType = new DataType();
			$dataType->setName($dataTypeName);
			$manager->persist($dataType);
			$this->addReference(strtolower($dataTypeName).'-datatype', $dataType);
		}
		$manager->flush();

        /*
         * Check Clock for time spent
         */
        $dummyDataTypesGenerationTime = $stopwatch->stop('dummyDataTypesGeneration');
        $duration = $dummyDataTypesGenerationTime->getDuration()/1000;
        unset($stopwatch);
        if( $duration <60 ) {
            $durationMessage = round($duration,2).' seconds';
        }elseif( $duration >= 60 && $duration < 3600 ) {
            $durationMessage = round(($duration/60),2) .' minutes';
        }elseif( $duration >=3600 && $duration < 216000) {
            $durationMessage = round(($duration/3600),2) .' hours';
        }else {
            $durationMessage = round(($duration/86400),2) .' hours';
        }
        //echo "Dummy Data Types generation complete in ". $durationMessage .".\n\n";
	}
	
	/**
     * The order in which this fixture will be loaded
	 * @return integer
	 */
	public function getOrder()
	{
        // LoadInputType preceeds
		return 3;
        //LoadField follows
	}

}
