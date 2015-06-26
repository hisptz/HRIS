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
namespace Hris\IntergrationBundle\Command;

use Hris\FormBundle\Entity\ResourceTable;
use Hris\IntergrationBundle\Entity\DataelementFieldOptionRelation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Stopwatch\Stopwatch;

class SyncDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hris:dhisintegration:syncdata')
            ->setDescription('Sync HRHIS Data to DHIS')
            ->addArgument('id', InputArgument::OPTIONAL, 'Intergration Connection Id')
            ->addArgument('year', InputArgument::OPTIONAL, 'Year of analysis')
            ->addArgument('orgunitid', InputArgument::OPTIONAL, 'Organisation unit id')
            ->setHelp(<<<EOT
The <info>hris:dhisintegration:syncdata</info> command generates xml with aggregate data to be sent to dhis

  <info>php app/console hris:dhisintegration:syncdata</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();
        $logger = $this->getContainer()->get('logger');

        $year = (date("Y")-1);
        $interval=20;
        $stopwatch = new Stopwatch();
        $stopwatch->start('syncing');

        $id = $input->getArgument('id');
        $capturedYear = $input->getArgument('year');
        $orgunitid = $input->getArgument('orgunitid');
        $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($id);
        if(!empty($orgunitid)) {
            $orgunit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->find($orgunitid);
        }else {
            $orgunit=$entity->getParentOrganisationunit();
        }
        if(!empty($capturedYear)) $year= $capturedYear;
        if ($id) {
            $this->dhisConnectionId = $id;
        } else {
            throw new NotFoundHttpException("Data connection not found!");
        }


        $this->xmlContents = "<?xml version='1.0' encoding='UTF-8'?>
<dataValueSet xmlns=\"http://dhis2.org/schema/dxf/2.0\">";

        $xmlFile = "/tmp/hrhis_data_".str_replace(' ','_',$orgunit).".xml";
        file_put_contents($xmlFile,$this->xmlContents);

        // Aggregate data for each orgunit in the current level.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($this->dhisConnectionId);
        /*
         * Initializing query for dhis dataset calculation
         */
        // Get Standard Resource table name
        $resourceTableName = str_replace(' ','_',trim(strtolower( ResourceTable::getStandardResourceTableName())));
        $resourceTableAlias="ResourceTable";


        /*
         * Aggregate organisationunit for all the children
         */
        $queryBuilder = $em->createQueryBuilder();
        $allOrganisationunitsChildren = $queryBuilder->select('DISTINCT organisationunit')
            ->from('HrisOrganisationunitBundle:Organisationunit','organisationunit')
            ->join('organisationunit.organisationunitStructure','organisationunitStructure')
            ->join('organisationunitStructure.level','level')
            ->andWhere('
                        (
                            level.level >= :organisationunitLevel
                            AND organisationunitStructure.level'.$orgunit->getOrganisationunitStructure()->getLevel()->getLevel().'Organisationunit=:levelOrganisationunit
                        ) AND organisationunit.dhisUid is not null
                        AND organisationunit.id IN ( SELECT DISTINCT(recordOrganisationunit.id) FROM HrisRecordsBundle:Record record INNER JOIN record.organisationunit recordOrganisationunit )'
            )
            ->setParameters(array(
                'levelOrganisationunit'=>$orgunit,
                'organisationunitLevel'=>$orgunit->getOrganisationunitStructure()->getLevel()->getLevel()
            ))
            ->getQuery()->getResult();

        // Query for Options to exclude from reports
        $fieldOptionsToSkip = $this->getContainer()->get('doctrine')->getManager()->getRepository('HrisFormBundle:FieldOption')->findBy (array('skipInReport' =>True));
        //filter the records with exclude report tag
        foreach($fieldOptionsToSkip as $key => $fieldOptionToSkip){
            if(empty($fieldOptionsToSkipQuery)) {
                $fieldOptionsToSkipQuery = "$resourceTableAlias.".$fieldOptionToSkip->getField()->getName()." !='".$fieldOptionToSkip->getValue()."'";
            }else {
                $fieldOptionsToSkipQuery .= " AND $resourceTableAlias.".$fieldOptionToSkip->getField()->getName()." !='".$fieldOptionToSkip->getValue()."'";
            }
        }
        $dataValueColumnName=NULL;
        $selectQuery = NULL;
        $organisationunitNames = NULL;
        $incr=0;
        $totalIncr=0;



        $lastLap = $stopwatch->lap('syncing');
        $lastLapDuration = round(($lastLap->getDuration()/1000),2);
        $previousTotalLapTime = round(($lastLap->getDuration()/1000),2);

        foreach($allOrganisationunitsChildren as $organisationunitKey=>$organisationunit) {
            $organisationunitNames .= $organisationunit->getLongname().', ';
            $incr++;
            // Aggregate data for each orgunit in the current level.
            $em = $this->getContainer()->get('doctrine')->getManager();
            $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($this->dhisConnectionId);

            $fromClause=" FROM $resourceTableName $resourceTableAlias ";

            $organisationunitLevelsWhereClause = " $resourceTableAlias.organisationunit_id=".$organisationunit->getId()." ";

            // Dataelement field option relation
            $dataelementFieldOptionRelation = $entity->getDataelementFieldOptionRelation();
            foreach($dataelementFieldOptionRelation as $dataelementFieldOptionKey=>$dataelementFieldOptionValue) {
                // Formulate Query for calculating field option
                $columnFieldOptionGroup = $dataelementFieldOptionValue->getColumnFieldOptionGroup();
                $rowFieldOptionGroup = $dataelementFieldOptionValue->getRowFieldOptionGroup();

                $seriesFieldName=$rowFieldOptionGroup->getName();

                //Column Query construction
                $queryColumnNames[] = str_replace('-','_',str_replace(' ','',$columnFieldOptionGroup->getName()));
                $categoryColumnFieldNames[] = $columnFieldOptionGroup->getField()->getName();
                $categoryRowFieldName = $columnFieldOptionGroup->getField()->getName();
                $columnWhereClause = NULL;

                foreach($columnFieldOptionGroup->getFieldOption() as $columnFieldOptionKey=>$columnFieldOption) {
                    $operator = $columnFieldOptionGroup->getOperator();
                    if(empty($operator)) $operator = "OR";
                    $categoryColumnFieldOptionValue=str_replace('-','_',$columnFieldOption->getValue());
                    $categoryColumnFieldName=$columnFieldOption->getField()->getName();
                    $categoryColumnResourceTableName=$resourceTableAlias;
                    if(!empty($columnWhereClause)) {
                        $columnWhereClause = $columnWhereClause." ".strtoupper($operator)." $categoryColumnResourceTableName.$categoryColumnFieldName='".$categoryColumnFieldOptionValue."'";
                    }else {
                        $columnWhereClause = "$categoryColumnResourceTableName.$categoryColumnFieldName='".$categoryColumnFieldOptionValue."'";
                    }

                }
                // Row Query construction
                $rowWhereClause = NULL;
                foreach($rowFieldOptionGroup->getFieldOption() as $rowFieldOptionKey=>$rowFieldOption) {
                    $operator = $rowFieldOptionGroup->getOperator();
                    if(empty($operator)) $operator = "OR";
                    $categoryRowFieldOptionValue=str_replace('-','_',$rowFieldOption->getValue());
                    $categoryRowFieldName=$rowFieldOption->getField()->getName();
                    $categoryRowResourceTableName=$resourceTableAlias;
                    if(!empty($rowWhereClause)) {
                        $rowWhereClause = $rowWhereClause." ".strtoupper($operator)." $categoryRowResourceTableName.$categoryRowFieldName='".$categoryRowFieldOptionValue."'";
                    }else {
                        $rowWhereClause = "$categoryRowResourceTableName.$categoryRowFieldName='".$categoryRowFieldOptionValue."'";
                    }
                }

                $verboseDataValaueColumnNames= $dataelementFieldOptionValue->getRowFieldOptionGroup()."--".$dataelementFieldOptionValue->getColumnFieldOptionGroup();
                $dataValueColumnName=$organisationunit->getDhisUid().'--'.$dataelementFieldOptionValue->getDataelementUid().'--'.$dataelementFieldOptionValue->getCategoryComboUid();
                if(!empty($selectQuery)) {
                    $selectQuery.=" UNION ALL SELECT CAST('".$dataValueColumnName."' AS text) AS datavaluelabel, COUNT(DISTINCT(instance)) AS value "." $fromClause WHERE ($rowWhereClause) AND ($columnWhereClause) AND ".$resourceTableAlias.".first_appointment_year<=".$year." AND $organisationunitLevelsWhereClause".( !empty($fieldOptionsToSkipQuery) ? " AND ( $fieldOptionsToSkipQuery )" : "" )." HAVING COUNT(DISTINCT(instance))>0";
                }else {
                    $selectQuery="SELECT CAST('".$dataValueColumnName."' AS text) AS datavaluelabel, COUNT(DISTINCT(instance)) AS value "." $fromClause WHERE ($rowWhereClause) AND ($columnWhereClause) AND ".$resourceTableAlias.".first_appointment_year<=".$year." AND $organisationunitLevelsWhereClause".( !empty($fieldOptionsToSkipQuery) ? " AND ( $fieldOptionsToSkipQuery )" : "" )." HAVING COUNT(DISTINCT(instance))>0";
                }
                unset($dhisUid);
                // Intercept after certain number of orgunits for fetching results
                // So as not to exceed database max_stack_depth
                if($incr>=$interval) {
                    // Reset counter
                    $totalIncr = $incr+ $totalIncr;
                    $incr=0;
                    // Process SQL Batch
                    $sqlResult = $this->getContainer()->get('doctrine')->getManager()->getConnection()->fetchAll($selectQuery);
                    foreach($sqlResult as $resultKey=>$resultRow) {
                        $dataValueKeys = explode('--',$resultRow['datavaluelabel']);//dhisUid--dataelementUid--categoryComboUid$recordRow ='<!--'.$resultRow['datavaluelabelverbose'].'-->';
                        $recordRow = '<dataValue orgUnit="'.$dataValueKeys[0].'" period="'.$year.'" dataElement="'.$dataValueKeys[1].'" categoryOptionCombo="'.$dataValueKeys[2].'" value="'.$resultRow['value'].'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />';
                        file_put_contents($xmlFile,$recordRow,FILE_APPEND);
                    }
                    $currentLap = $stopwatch->lap('syncing');
                    $currentLapDuration = NULL;
                    $currentLapDuration = round(($currentLap->getDuration()/1000),2) - $previousTotalLapTime;

                    if( $currentLapDuration <60 ) {
                        $durationMessage = round($currentLapDuration,2).' seconds';
                    }elseif( $currentLapDuration >= 60 && $currentLapDuration < 3600 ) {
                        $durationMessage = round(($currentLapDuration/60),2) .' minutes';
                    }elseif( $currentLapDuration >=3600 && $currentLapDuration < 216000) {
                        $durationMessage = round(($currentLapDuration/3600),2) .' hours';
                    }else {
                        $durationMessage = round(($currentLapDuration/86400),2) .' hours';
                    }
                    $lastLapDuration = NULL;
                    $lastLapDuration = $currentLapDuration;

                    $previousTotalLapTime = round(($currentLap->getDuration()/1000),2);


                    $output->writeln('Fetched records for '.$totalIncr.' out of '.count($allOrganisationunitsChildren).' organisationunits in '.$durationMessage." ".count($sqlResult)." results found");
                    //$output->writeln('Organisationunits parsed:'."\n".$organisationunitNames."\n");
                    $selectQuery=NULL;
                    $organisationunitNames = NULL;
                }
            }
        }

        // Process last remaining SQL Batch
        $sqlResult = $this->getContainer()->get('doctrine')->getManager()->getConnection()->fetchAll($selectQuery);
        foreach($sqlResult as $resultKey=>$resultRow) {
            $dataValueKeys = explode('--',$resultRow['datavaluelabel']);//dhisUid--dataelementUid--categoryComboUid
            $recordRow = '<dataValue orgUnit="'.$dataValueKeys[0].'" period="'.$year.'" dataElement="'.$dataValueKeys[1].'" categoryOptionCombo="'.$dataValueKeys[2].'" value="'.$resultRow['value'].'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />';
            file_put_contents($xmlFile,$recordRow,FILE_APPEND);
        }
        $currentLap = $stopwatch->lap('syncing');
        $currentLapDuration = NULL;
        $currentLapDuration = round(($currentLap->getDuration()/1000),2) - $previousTotalLapTime;

        if( $currentLapDuration <60 ) {
            $durationMessage = round($currentLapDuration,2).' seconds';
        }elseif( $currentLapDuration >= 60 && $currentLapDuration < 3600 ) {
            $durationMessage = round(($currentLapDuration/60),2) .' minutes';
        }elseif( $currentLapDuration >=3600 && $currentLapDuration < 216000) {
            $durationMessage = round(($currentLapDuration/3600),2) .' hours';
        }else {
            $durationMessage = round(($currentLapDuration/86400),2) .' hours';
        }
        $lastLapDuration = NULL;
        $lastLapDuration = $currentLapDuration;

        $previousTotalLapTime = round(($currentLap->getDuration()/1000),2);


        $output->writeln('Fetched records for '.$interval.' out of '.count($allOrganisationunitsChildren).' organisationunits in '.$durationMessage." ".count($sqlResult)." results found ");
        //$output->writeln('Organisationunits parsed:'."\n".$organisationunitNames."\n");
        $selectQuery=NULL;
        $organisationunitNames = NULL;

        $this->xmlContents = $this->xmlContents.'</dataValueSet>';
        file_put_contents($xmlFile,'</dataValueSet>',FILE_APPEND);

        /*
         * Check Clock for time spent
         */
        $totalTime = $stopwatch->stop('syncing');
        $duration = $totalTime->getDuration()/1000;
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
        $output->writeln("Data Syncing completed in ". $durationMessage .".\n\n");

        $this->messageLog = "Sync aggregation for ".$orgunit->getLongname()." operation is complete";

        $output->writeln($this->messageLog);
    }

    /**
     * Get all values from specific key in a multidimensional array
     *
     * @param $key string
     * @param $arr array
     * @return null|string|array
     */
    public function array_value_recursive($key, array $arr){
        $val = array();
        array_walk_recursive($arr, function($v, $k) use($key, &$val){if($k == $key) array_push($val, $v);});
        return count($val) > 1 ? $val : array_pop($val);
    }

    /**
     * @var string
     */
    private $dhisConnectionId;

    /**
     * @var string
     */
    private $xmlContents;

    /**
     * @var string
     */
    private $messageLog;
}