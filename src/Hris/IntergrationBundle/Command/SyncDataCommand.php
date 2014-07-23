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

class SyncDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hris:dhisintegration:syncdata')
            ->setDescription('Sync HRHIS Data to DHIS')
            ->addArgument('id', InputArgument::OPTIONAL, 'Intergration Connection Id')
            ->setHelp(<<<EOT
The <info>hris:dhisintegration:syncdata</info> command generates xml with aggregate data to be sent to dhis

  <info>php app/console hris:dhisintegration:syncdata</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        if ($id) {
            $this->dhisConnectionId = $id;
        } else {
            throw new NotFoundHttpException("Data connection not found!");
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $logger = $this->getContainer()->get('logger');

        $this->xmlContents = "<?xml version='1.0' encoding='UTF-8'?>
<dataValueSet xmlns=\"http://dhis2.org/schema/dxf/2.0\">";

        $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($id);
        $xmlFile = "/tmp/hrhis_data_".str_replace(' ','_',$entity->getParentOrganisationunit()).".xml";
        file_put_contents($xmlFile,$this->xmlContents);

        /*
         * Aggregate data for the parent organisationunit
         */
        // Aggregate data for each orgunit in the current level.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($this->dhisConnectionId);
        /*
         * Initializing query for dhis dataset calculation
         */
        // Get Standard Resource table name
        $resourceTableName = str_replace(' ','_',trim(strtolower( ResourceTable::getStandardResourceTableName())));
        $resourceTableAlias="ResourceTable";

        //@todo create join clause to only go through orgunit with dhisUid

        $fromClause=" FROM $resourceTableName $resourceTableAlias ";

        $organisationunitLevelsWhereClause = " $resourceTableAlias.organisationunit_id=".$entity->getParentOrganisationunit()->getId()." ";

        // Query for Options to exclude from reports
        $fieldOptionsToSkip = $em->getRepository('HrisFormBundle:FieldOption')->findBy (array('skipInReport' =>True));
        //filter the records with exclude report tag
        foreach($fieldOptionsToSkip as $key => $fieldOptionToSkip){
            if(empty($fieldOptionsToSkipQuery)) {
                $fieldOptionsToSkipQuery = "$resourceTableAlias.".$fieldOptionToSkip->getField()->getName()." !='".$fieldOptionToSkip->getValue()."'";
            }else {
                $fieldOptionsToSkipQuery .= " AND $resourceTableAlias.".$fieldOptionToSkip->getField()->getName()." !='".$fieldOptionToSkip->getValue()."'";
            }
        }

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

            $selectQuery="SELECT COUNT(DISTINCT(instance)) $fromClause WHERE ($rowWhereClause) AND ($columnWhereClause) AND $organisationunitLevelsWhereClause".( !empty($fieldOptionsToSkipQuery) ? " AND ( $fieldOptionsToSkipQuery )" : "" );
            $instanceCount = $this->array_value_recursive('count',$this->getContainer()->get('doctrine')->getManager()->getConnection()->fetchAll($selectQuery));
            $dhisUid=$entity->getParentOrganisationunit()->getDhisUid();
            if(!empty($dhisUid) && ($instanceCount>0)) {
                //$this->xmlContents = $this->xmlContents.'<dataValue dataElement="'.$dataelementFieldOptionValue->getDataelementUid().'" period="'.date("Y").'" orgUnit="'.$entity->getParentOrganisationunit()->getDhisUid().'" categoryOptionCombo="'.$dataelementFieldOptionValue->getCategoryComboUid().'" value="'.$instanceCount.'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />';
                file_put_contents($xmlFile,'<dataValue dataElement="'.$dataelementFieldOptionValue->getDataelementUid().'" period="'.date("Y").'" orgUnit="'.$entity->getParentOrganisationunit()->getDhisUid().'" categoryOptionCombo="'.$dataelementFieldOptionValue->getCategoryComboUid().'" value="'.$instanceCount.'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />',FILE_APPEND);
            }
            unset($dhisUid);
        }

        /*
         * Aggregate organisationunit for all the children
         */
        $queryBuilder = $em->createQueryBuilder();
        $allOrganisationunitsChildren = $queryBuilder->select('organisationunit')
            ->from('HrisOrganisationunitBundle:Organisationunit','organisationunit')
            ->join('organisationunit.organisationunitStructure','organisationunitStructure')
            ->join('organisationunitStructure.level','level')
            ->andWhere('
                        (
                            level.level >= :organisationunitLevel
                            AND organisationunitStructure.level'.$entity->getParentOrganisationunit()->getOrganisationunitStructure()->getLevel()->getLevel().'Organisationunit=:levelOrganisationunit
                        ) AND organisationunit.dhisUid is not null
                        AND organisationunit.id!='.$entity->getParentOrganisationunit()->getId().'
                        AND organisationunit.id IN ( SELECT DISTINCT(recordOrganisationunit.id) FROM HrisRecordsBundle:Record record INNER JOIN record.organisationunit recordOrganisationunit )'
            )
            ->setParameters(array(
                'levelOrganisationunit'=>$entity->getParentOrganisationunit(),
                'organisationunitLevel'=>$entity->getParentOrganisationunit()->getOrganisationunitStructure()->getLevel()->getLevel()
            ))
            ->getQuery()->getResult();

        foreach($allOrganisationunitsChildren as $organisationunitKey=>$organisationunit) {
            // Aggregate data for each orgunit in the current level.
            $em = $this->getContainer()->get('doctrine')->getManager();
            $entity = $em->getRepository('HrisIntergrationBundle:DHISDataConnection')->find($this->dhisConnectionId);
            /*
             * Initializing query for dhis dataset calculation
             */
            // Get Standard Resource table name
            $resourceTableName = str_replace(' ','_',trim(strtolower( ResourceTable::getStandardResourceTableName())));
            $resourceTableAlias="ResourceTable";

            //@todo create join clause to only go through orgunit with dhisUid

            $fromClause=" FROM $resourceTableName $resourceTableAlias ";

            $organisationunitLevelsWhereClause = " $resourceTableAlias.organisationunit_id=".$organisationunit->getId()." ";

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

                $selectQuery="SELECT COUNT(DISTINCT(instance)) $fromClause WHERE ($rowWhereClause) AND ($columnWhereClause) AND $organisationunitLevelsWhereClause".( !empty($fieldOptionsToSkipQuery) ? " AND ( $fieldOptionsToSkipQuery )" : "" );
                $instanceCount = $this->array_value_recursive('count',$this->getContainer()->get('doctrine')->getManager()->getConnection()->fetchAll($selectQuery));
                //Only send non-zero data
                if($instanceCount>0) {
                    //$this->xmlContents = $this->xmlContents.'<dataValue dataElement="'.$dataelementFieldOptionValue->getDataelementUid().'" period="'.date("Y").'" orgUnit="'.$organisationunit->getDhisUid().'" categoryOptionCombo="'.$dataelementFieldOptionValue->getCategoryComboUid().'" value="'.$instanceCount.'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />';
                    file_put_contents($xmlFile,'<dataValue dataElement="'.$dataelementFieldOptionValue->getDataelementUid().'" period="'.date("Y").'" orgUnit="'.$organisationunit->getDhisUid().'" categoryOptionCombo="'.$dataelementFieldOptionValue->getCategoryComboUid().'" value="'.$instanceCount.'" storedBy="hrhis" lastUpdated="'.date("c").'" followUp="false" />',FILE_APPEND);
                    $logger->info('Inserted record for '.$dataelementFieldOptionValue->getDataelementname().' '.$dataelementFieldOptionValue->getCategoryComboname() .' '.$organisationunit->getLongname());
                    //echo 'Inserted record for '.$dataelementFieldOptionValue->getDataelementname().' '.$dataelementFieldOptionValue->getCategoryComboname() .' '.$organisationunit->getLongname()."\n";
                }

                unset($dhisUid);
            }
        }

        $this->xmlContents = $this->xmlContents.'</dataValueSet>';
        file_put_contents($xmlFile,'</dataValueSet>',FILE_APPEND);


        // Initializing export file
        $outputFile =
        $fileSystem = new Filesystem();
        $exportFileName = "Export_".date("Y_m_d_His").".zip";
        $exportArchive = new ZipArchive();
        $exportArchive->open("/tmp/".$exportFileName,ZipArchive::CREATE);
        $exportArchive->addFromString("Export_".date("Y_m_d_His").'xml',$this->xmlContents);
        $exportArchive->close();
        $fileSystem->chmod("/tmp/".$exportFileName,0666);
        $response = new Response(file_get_contents("/tmp/".$exportFileName));
        $d = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $exportFileName);
        $response->headers->set('Content-Disposition', $d);

        unlink("/tmp/".$exportFileName);

        $result = $this->xmlContents;

        $this->messageLog = "Sync aggregation operation is complete";

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