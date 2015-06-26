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
namespace Hris\FormBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Hris\FormBundle\Entity\ResourceTable;

class ExportMetadataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hris:export:metadata')
            ->setDescription('Generate metadata')
            ->addArgument('name', InputArgument::OPTIONAL, 'Resourcetable name')
            ->addOption('json', null, InputOption::VALUE_NONE, 'If set, then output will be in json format')
            ->addOption('xml', null, InputOption::VALUE_NONE,'If set, then output will be in xml')
            ->setHelp(<<<EOT
The <info>hris:export:metadata</info> command generates metadata for human resource

  <info>php app/console hris:export:metadata</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hr_prefix="hr_";
        $normalizer = new GetSetMethodNormalizer();
        //Setup Custom dropdown fields
        $this->customDropDown = array(
            "level1_mohsw",
            "level2_categories",
            "level3_regions_departments_institutions_referrals",
            "level4_districts_reg_hospitals",
            "level5_facility","type","ownership","organisationunit_name","form_name"
        );

        $name = $input->getArgument('name');
        if ($name) {
            $this->resourceTableName = $name;
        } else {
            $this->resourceTableName = 'All Fields';
        }

        if ($input->getOption('json')) {
            $this->resourceTableNature = 'json';
            $encoder = new JsonEncoder();
        }elseif($input->getOption('xml')) {
            $this->resourceTableNature = 'xml';
            $encoder = new XmlEncoder();
        }else {
            $this->resourceTableNature = 'json';
            $encoder = new JsonEncoder();
        }

        //$normalizer->setIgnoredAttributes(array('age'));

        $metaData = array();
        $metaData["created"] = (new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $logger = $this->getContainer()->get('logger');

        // Find Resource table for generation
        $resourceTableEntity = $em->getRepository('HrisFormBundle:ResourceTable')->findOneBy(array('name'=>$this->resourceTableName));
        
        //Defining program and programStage for human resource data
        $humanResourceProgram= array();
        $humanResourceProgram["name"] =$hr_prefix."HumanResource";
        //$humanResourceProgram["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$humanResourceProgram["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        $humanResourceProgram["externalAccess"]="false";
        $humanResourceProgram["id"]="Gcghe0W76Ms";
        
        $humanResourceProgramStage= array();
        $humanResourceProgramStage["name"] =$hr_prefix."Single-Event HumanResource";
        //$humanResourceProgramStage["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$humanResourceProgramStage["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        $humanResourceProgramStage["externalAccess"]="false";
        $humanResourceProgramStage["id"]="NHIu1snluiZ";

        //Construct dataElement meta-data for the resource table
        // Create other columns(fields, organisationunits,etc) in the resource table
        $dataElements = array();
        $programStageDataElements = array();
        $optionSets = array();
        $allOptions = array();
        $sensitiveFields = array("firstname","middlename","surname","file_no","reg_no","check_no","salary","religion","next_kin","contact_of_next_of_kin");


		//Months optionsets
		//Place optionsets
		$monthOptionSet = array();
		$monthOptionSet["name"] =$hr_prefix.'Month_text';
		$monthOptionSet["code"] ='Month_text';
        $monthOptionSet["id"] ="o".substr(md5($monthOptionSet["name"]),0,10);
		//$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
		//$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
		$monthOptionSet["externalAccess"]="false";
		$monthOptionSet["publicAccess"]="rw------";
		$monthOptionSet["version"]="1";
		$options = array();
		$monthsArray = array("January","February","March","April","May","June","July","August","September","October","November","December");

		foreach($monthsArray as $monthKey=>$month) {
			$option = array();
			$option["name"]=$month;
			$option["code"]=$month;
            $option["id"] ="o".substr(md5($option["name"]),0,10);
			//$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
			//$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
			$option["externalAccess"]="false";
			$options[]=$option;
            $allOptions[]=$option;
			unset($option);
		}
		$monthOptionSet["options"]=$options;
		$optionSets[]=$monthOptionSet;
		unset($options);

		//Go through resource table fields to create dataelements metadata
        foreach($resourceTableEntity->getResourceTableFieldMember() as $resourceTableKey=> $resourceTableFieldMember) {

            $field = $resourceTableFieldMember->getField();
            //Sensitive fields to skip
			if(in_array(strtolower($field->getName()),$sensitiveFields)) continue;
            //Prepare a data element
            $dataElement = array();

            $dataElement["name"] =$hr_prefix. $field->getName();
            $dataElement["code"] = $field->getName();
            $dataElement["id"] = "F".substr($field->getUid(),3);
            //$dataElement["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
            //$dataElement["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
            //Inserting minimum dataElementInfo into programStageDataElement
            $programStageDataElement = array();
            $programStageDataElement["programStage"]=$humanResourceProgramStage;
            $programStageDataElement["dataElement"]=$dataElement;
            $programStageDataElement["compulsory"]= "false";
            $programStageDataElement["allowProvidedElsewhere"]= "false";
            $programStageDataElement["sortOrder"]= "0";
            $programStageDataElement["displayInReports"]= "false";
            $programStageDataElement["allowFutureDate"]= "false";
            $programStageDataElements[]=$programStageDataElement;
            //continue with preparting dataElement
            $dataElement["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName())),0,49);
            $dataElement["domainType"]="TRACKER";
            $dataElement["aggregationOperator"]="sum";

            if($field->getDataType()->getName() == "String" ) {
                //For string place
                $dataElement["type"] = "string";
                $dataElement["textType"] = "text";
            }elseif($field->getDataType()->getName() == "Integer") {
                $dataElement["type"] = "int";
                $dataElement["numberType"]="int";
            }elseif($field->getDataType()->getName() == "Double") {
                $dataElement["type"] = "int";
                $dataElement["numberType"] = "number";
            }elseif($field->getDataType()->getName() == "Date") {
                $dataElement["type"]="date";
                $dataElement["numberType"]="number";
                // Additional date extrapolation columns

                $dataElementMonthText = array();
                $dataElementMonthText["name"] =$hr_prefix. $field->getName().'_month_text';
                $dataElementMonthText["code"] = $field->getName().'_month_text';
                $dataElementMonthText["id"] = "Fmt".substr($field->getUid(),5);
                //$dataElementMonthText["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
                //$dataElementMonthText["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
                //Inserting minimum dataElementInfo into programStageDataElement
                $programStageDataElement = array();
                $programStageDataElement["programStage"]=$humanResourceProgramStage;
                $programStageDataElement["dataElement"]=$dataElementMonthText;
                $programStageDataElement["compulsory"]= "false";
                $programStageDataElement["allowProvidedElsewhere"]= "false";
                $programStageDataElement["sortOrder"]= "0";
                $programStageDataElement["displayInReports"]= "false";
                $programStageDataElement["allowFutureDate"]= "false";
                $programStageDataElements[]=$programStageDataElement;
                //continue with preparting dataElement
                $dataElementMonthText["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName().'_month_text')),0,49);
                $dataElementMonthText["domainType"]="TRACKER";
                $dataElementMonthText["aggregationOperator"]="sum";
                $dataElementMonthText["type"]="string";
                $dataElementMonthText["textType"]="text";
                //Place optionsets
                $dataElementMonthText["optionSet"]=$monthOptionSet;
                $dataElements[]=$dataElementMonthText;
                unset($dataElementMonthText);

                $dataElementYear = array();
                $dataElementYear["name"] =$hr_prefix. $field->getName().'_year';
                $dataElementYear["code"] = $field->getName().'_year';
                $dataElementYear["id"] = "Fyr".substr($field->getUid(),5);
                //$dataElementYear["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
                //$dataElementYear["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
                //Inserting minimum dataElementInfo into programStageDataElement
                $programStageDataElement = array();
                $programStageDataElement["programStage"]=$humanResourceProgramStage;
                $programStageDataElement["dataElement"]=$dataElementYear;
                $programStageDataElement["compulsory"]= "false";
                $programStageDataElement["allowProvidedElsewhere"]= "false";
                $programStageDataElement["sortOrder"]= "0";
                $programStageDataElement["displayInReports"]= "false";
                $programStageDataElement["allowFutureDate"]= "false";
                $programStageDataElements[]=$programStageDataElement;
                //continue with preparting dataElement
                $dataElementYear["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName().'_year')),0,49);
                $dataElementYear["domainType"]="TRACKER";
                $dataElementYear["aggregationOperator"]="sum";
                $dataElementYear["type"]="int";
                $dataElementYear["numberType"]="int";
                $dataElements[]=$dataElementYear;
                unset($dataElementYear);

            }

            if($field->getInputType()->getName() == "Select" ) {
                $optionSet = array();
                $optionSet["name"] =$hr_prefix.$field->getName();
                $optionSet["code"] =substr(str_replace("_","",str_replace(" ","",$field->getName())),0,49);
                $optionSet["id"] ="o".substr(md5($optionSet["name"]),0,10);
                //$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                //$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                $optionSet["externalAccess"]="false";
                $optionSet["publicAccess"]="rw------";
                $optionSet["version"]="1";
                $options = array();


                //Populate optionsets for dropdown fields//create the query to aggregate the records from the static resource table
                //check if field one is calculating field so to create the sub query
                $resourceTableName = ResourceTable::getStandardResourceTableName();
                $query="SELECT DISTINCT ResourceTable.".$field->getName()." FROM ".$resourceTableName." ResourceTable";
                $fieldOptionsResults = $em -> getConnection() -> executeQuery($query) -> fetchAll();
                $fieldOptionsResults = $resourceTableEntity->array_value_recursive(strtolower($field->getName()),$fieldOptionsResults);
                if(!empty($fieldOptionsResults) && is_array($fieldOptionsResults)) {
                    foreach($fieldOptionsResults as $fieldOptionsResultKey=>$fieldOptionsResult) {
                        $option = array();
                        if(!empty($fieldOptionsResult)) {
                            $option["name"]=$fieldOptionsResult;
                            $option["code"]=$fieldOptionsResult;
                            $option["id"] ="o".substr(md5($option["name"]),0,10);
                            //$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                            //$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                            $option["externalAccess"]="false";
                            $options[]=$option;
                            $allOptions[]=$option;
                            unset($option);
                        }
                    }
                    $optionSet["options"]=$options;
                    $optionSets[]=$optionSet;
                    unset($options);
                }else if(!empty($fieldOptionsResults) && is_string($fieldOptionsResults)) {
					$option = array();
					$option["name"]=$fieldOptionsResults;
					$option["code"]=$fieldOptionsResults;
                    $option["id"] ="o".substr(md5($option["name"]),0,10);
					$option["externalAccess"]="false";
					$options[]=$option;
                    $allOptions[]=$option;
					unset($option);
					$optionSet["options"]=$options;
                    $optionSets[]=$optionSet;
                    unset($options);
				}
                $dataElement["optionSet"]=$optionSet;
            }
            $dataElements[]=$dataElement;
            unset($dataElement);

            // @todo implement after creation of history date class
            // Add History date field for fields with history

            if($field->getHashistory()) {
                $dataElementHistoryLastUpdated = array();
                $dataElementHistoryLastUpdated["name"] =$hr_prefix. $field->getName().'_last_updated';
                $dataElementHistoryLastUpdated["code"] = substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated')),0,49);
                $dataElementHistoryLastUpdated["id"] = "Flu".substr($field->getUid(),5);
                //$dataElementHistoryLastUpdated["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
                //$dataElementHistoryLastUpdated["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
                //Inserting minimum dataElementInfo into programStageDataElement
                $programStageDataElement = array();
                $programStageDataElement["programStage"]=$humanResourceProgramStage;
                $programStageDataElement["dataElement"]=$dataElementHistoryLastUpdated;
                $programStageDataElement["compulsory"]= "false";
                $programStageDataElement["allowProvidedElsewhere"]= "false";
                $programStageDataElement["sortOrder"]= "0";
                $programStageDataElement["displayInReports"]= "false";
                $programStageDataElement["allowFutureDate"]= "false";
                $programStageDataElements[]=$programStageDataElement;
                //continue with preparting dataElement
                $dataElementHistoryLastUpdated["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated')),0,49);
                $dataElementHistoryLastUpdated["domainType"]="TRACKER";
                $dataElementHistoryLastUpdated["aggregationOperator"]="sum";
                $dataElementHistoryLastUpdated["type"]="date";
                $dataElementHistoryLastUpdated["numberType"]="number";
                $dataElements[]=$dataElementHistoryLastUpdated;
                unset($dataElementHistoryLastUpdated);
                // Additional analysis columns

                $dataElementHistoryLastUpdatedMonthText = array();
                $dataElementHistoryLastUpdatedMonthText["name"] =$hr_prefix. $field->getName().'_last_updated_month_text';
                $dataElementHistoryLastUpdatedMonthText["code"] = substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated_month_text')),0,49);
                $dataElementHistoryLastUpdatedMonthText["id"] = "Fhmt".substr($field->getUid(),6);
                //$dataElementHistoryLastUpdatedMonthText["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
                //$dataElementHistoryLastUpdatedMonthText["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
                //Inserting minimum dataElementInfo into programStageDataElement
                $programStageDataElement = array();
                $programStageDataElement["programStage"]=$humanResourceProgramStage;
                $programStageDataElement["dataElement"]=$dataElementHistoryLastUpdatedMonthText;
                $programStageDataElement["compulsory"]= "false";
                $programStageDataElement["allowProvidedElsewhere"]= "false";
                $programStageDataElement["sortOrder"]= "0";
                $programStageDataElement["displayInReports"]= "false";
                $programStageDataElement["allowFutureDate"]= "false";
                $programStageDataElements[]=$programStageDataElement;
                //continue with preparting dataElement
                $dataElementHistoryLastUpdatedMonthText["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated_month_text')),0,49);
                $dataElementHistoryLastUpdatedMonthText["domainType"]="TRACKER";
                $dataElementHistoryLastUpdatedMonthText["aggregationOperator"]="sum";
                $dataElementHistoryLastUpdatedMonthText["type"]="string";
                $dataElementHistoryLastUpdatedMonthText["textType"]="text";
                //Place optionsets
                $dataElementHistoryLastUpdatedMonthText["optionSet"]=$monthOptionSet;

                $dataElements[]=$dataElementHistoryLastUpdatedMonthText;
                unset($dataElementHistoryLastUpdatedMonthText);

                $dataElementHistoryLastUpdatedYear = array();
                $dataElementHistoryLastUpdatedYear["name"] =$hr_prefix. $field->getName().'_last_updated_year';
                $dataElementHistoryLastUpdatedYear["code"] = substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated_year')),0,49);
                $dataElementHistoryLastUpdatedYear["id"] = "Fhyr".substr($field->getUid(),6);
                //$dataElementHistoryLastUpdatedYear["created"]=$field->getDatecreated()->format('Y-m-d\TH:i:s.000O');
                //$dataElementHistoryLastUpdatedYear["lastUpdated"]=$field->getLastupdated()->format('Y-m-d\TH:i:s.000O');
                //Inserting minimum dataElementInfo into programStageDataElement
                $programStageDataElement = array();
                $programStageDataElement["programStage"]=$humanResourceProgramStage;
                $programStageDataElement["dataElement"]=$dataElementHistoryLastUpdatedYear;
                $programStageDataElement["compulsory"]= "false";
                $programStageDataElement["allowProvidedElsewhere"]= "false";
                $programStageDataElement["sortOrder"]= "0";
                $programStageDataElement["displayInReports"]= "false";
                $programStageDataElement["allowFutureDate"]= "false";
                $programStageDataElements[]=$programStageDataElement;
                //continue with preparting dataElement
                $dataElementHistoryLastUpdatedYear["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$field->getName().'_last_updated_year')),0,49);
                $dataElementHistoryLastUpdatedYear["domainType"]="TRACKER";
                $dataElementHistoryLastUpdatedYear["aggregationOperator"]="sum";
                $dataElementHistoryLastUpdatedYear["type"]="int";
                $dataElementHistoryLastUpdatedYear["numberType"]="int";
                $dataElements[]=$dataElementHistoryLastUpdatedYear;
                unset($dataElementHistoryLastUpdatedYear);
            }

            unset($field);
        }



        // Make OrganisationunitLevels of orgunit
        $organisationunitLevels = $em->createQuery('SELECT DISTINCT organisationunitLevel FROM HrisOrganisationunitBundle:OrganisationunitLevel organisationunitLevel ORDER BY organisationunitLevel.level ')->getResult();
        foreach($organisationunitLevels as $organisationunitLevelKey=>$organisationunitLevel) {
            $organisationunitLevelName = "level".$organisationunitLevel->getLevel()."_".str_replace(',','_',str_replace('.','_',str_replace('/','_',str_replace(' ','_',$organisationunitLevel->getName())))) ;
            $dataElementOrgunitLevel = array();
            $dataElementOrgunitLevel["name"] =$hr_prefix. $organisationunitLevelName;
            $dataElementOrgunitLevel["code"] = substr(str_replace("_","",str_replace(" ","",$organisationunitLevelName)),0,49);
            $dataElementOrgunitLevel["id"] = "Lvl".$organisationunitLevel->getLevel().substr($organisationunitLevel->getUid(),6);
            //$dataElementOrgunitLevel["created"]=$organisationunitLevel->getDatecreated();
            //$dataElementOrgunitLevel["lastUpdated"]=$organisationunitLevel->getLastupdated();
            //Inserting minimum dataElementInfo into programStageDataElement
            $programStageDataElement = array();
            $programStageDataElement["programStage"]=$humanResourceProgramStage;
            $programStageDataElement["dataElement"]=$dataElementOrgunitLevel;
            $programStageDataElement["compulsory"]= "false";
            $programStageDataElement["allowProvidedElsewhere"]= "false";
            $programStageDataElement["sortOrder"]= "0";
            $programStageDataElement["displayInReports"]= "false";
            $programStageDataElement["allowFutureDate"]= "false";
            $programStageDataElements[]=$programStageDataElement;
            //continue with preparting dataElement
            $dataElementOrgunitLevel["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$organisationunitLevelName)),0,49);
            $dataElementOrgunitLevel["domainType"]="TRACKER";
            $dataElementOrgunitLevel["aggregationOperator"]="sum";
            $dataElementOrgunitLevel["type"]="string";
            $dataElementOrgunitLevel["textType"]="text";
            //Place optionsets
            $optionSet = array();
            $optionSet["name"] =$hr_prefix.$organisationunitLevelName;
            $optionSet["code"] =substr(str_replace("_","",str_replace(" ","",$organisationunitLevelName)),0,49);
            $optionSet["id"] ="o".substr(md5($optionSet["name"]),0,10);
            //$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
            //$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
            $optionSet["externalAccess"]="false";
            $optionSet["publicAccess"]="rw------";
            $optionSet["version"]="1";
            $options = array();


            //Populate optionsets for dropdown fields//create the query to aggregate the records from the static resource table
            //check if field one is calculating field so to create the sub query
            $resourceTableName = ResourceTable::getStandardResourceTableName();
            $query="SELECT DISTINCT ResourceTable.".$organisationunitLevelName." FROM ".$resourceTableName." ResourceTable";
            $fieldOptionsResults = $em -> getConnection() -> executeQuery($query) -> fetchAll();
            $fieldOptionsResults = $resourceTableEntity->array_value_recursive(strtolower($organisationunitLevelName),$fieldOptionsResults);
            
            if(!empty($fieldOptionsResults) && is_array($fieldOptionsResults)) {
                foreach($fieldOptionsResults as $fieldOptionsResultKey=>$fieldOptionsResult) {
                    $option = array();
                    if(!empty($fieldOptionsResult)) {
                        $option["name"]=$fieldOptionsResult;
                        $option["code"]=$fieldOptionsResult;
                        $option["id"] ="o".substr(md5($option["name"]),0,10);
                        //$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                        //$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                        $option["externalAccess"]="false";
                        $options[]=$option;
                        $allOptions[]=$option;
                        unset($option);
                    }
                }
                $optionSet["options"]=$options;
                $optionSets[]=$optionSet;
                unset($options);
            }else if ( !empty($fieldOptionsResults) && is_string($fieldOptionsResults) ) {
				$option = array();
				$option["name"]=$fieldOptionsResults;
				$option["code"]=$fieldOptionsResults;
                $option["id"] ="o".substr(md5($option["name"]),0,10);
				$option["externalAccess"]="false";
				$options[]=$option;
                $allOptions[]=$option;
				unset($option);
				$optionSet["options"]=$options;
                $optionSets[]=$optionSet;
                unset($options);
			}
            $dataElementOrgunitLevel["optionSet"]=$optionSet;
            $dataElements[]=$dataElementOrgunitLevel;
            unset($dataElementOrgunitLevel);
        }

        // Make OrganisationunitGroupsets Column
        $organisationunitGroupsets = $em->getRepository('HrisOrganisationunitBundle:OrganisationunitGroupset')->findAll();
        foreach($organisationunitGroupsets as $organisationunitGroupsetKey=>$organisationunitGroupset) {
            $dataElementOrgunitGroupset = array();
            $dataElementOrgunitGroupset["name"] =$hr_prefix. $organisationunitGroupset->getName();
            $dataElementOrgunitGroupset["code"] = substr(str_replace("_","",str_replace(" ","",$organisationunitGroupset->getName())),0,49);
            $dataElementOrgunitGroupset["id"] = "Grp".substr($organisationunitGroupset->getUid(),5);
            //$dataElementOrgunitGroupset["created"]=$organisationunitGroupset->getDatecreated();
            //$dataElementOrgunitGroupset["lastUpdated"]=$organisationunitGroupset->getLastupdated();
            //Inserting minimum dataElementInfo into programStageDataElement
            $programStageDataElement = array();
            $programStageDataElement["programStage"]=$humanResourceProgramStage;
            $programStageDataElement["dataElement"]=$dataElementOrgunitGroupset;
            $programStageDataElement["compulsory"]= "false";
            $programStageDataElement["allowProvidedElsewhere"]= "false";
            $programStageDataElement["sortOrder"]= "0";
            $programStageDataElement["displayInReports"]= "false";
            $programStageDataElement["allowFutureDate"]= "false";
            $programStageDataElements[]=$programStageDataElement;
            //continue with preparting dataElement
            $dataElementOrgunitGroupset["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",$organisationunitGroupset->getName())),0,49);
            $dataElementOrgunitGroupset["domainType"]="TRACKER";
            $dataElementOrgunitGroupset["aggregationOperator"]="sum";
            $dataElementOrgunitGroupset["type"]="string";
            $dataElementOrgunitGroupset["textType"]="text";
            //Place optionsets
            $optionSet = array();
            $optionSet["name"] =$hr_prefix.$organisationunitGroupset->getName();
            $optionSet["code"] =substr(str_replace("_","",str_replace(" ","",$organisationunitGroupset->getName())),0,49);
            $optionSet["id"] ="o".substr(md5($optionSet["name"]),0,10);
            //$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
            //$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
            $optionSet["externalAccess"]="false";
            $optionSet["publicAccess"]="rw------";
            $optionSet["version"]="1";
            $options = array();


            //Populate optionsets for dropdown fields//create the query to aggregate the records from the static resource table
            //check if field one is calculating field so to create the sub query
            $resourceTableName = ResourceTable::getStandardResourceTableName();
            $query="SELECT DISTINCT ResourceTable.".$organisationunitGroupset->getName()." FROM ".$resourceTableName." ResourceTable";
            $fieldOptionsResults = $em -> getConnection() -> executeQuery($query) -> fetchAll();
            $fieldOptionsResults = $resourceTableEntity->array_value_recursive(strtolower($organisationunitGroupset->getName()),$fieldOptionsResults);
            if(!empty($fieldOptionsResults) && is_array($fieldOptionsResults)) {
                foreach($fieldOptionsResults as $fieldOptionsResultKey=>$fieldOptionsResult) {
                    $option = array();
                    if(!empty($fieldOptionsResult)) {
                        $option["name"]=$fieldOptionsResult;
                        $option["code"]=$fieldOptionsResult;
                        $option["id"] ="o".substr(md5($option["name"]),0,10);
                        //$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                        //$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                        $optionSet["externalAccess"]="false";
                        $options[]=$option;
                        $allOptions[]=$option;
                        unset($option);
                    }
                }
                $optionSet["options"]=$options;
                $optionSets[]=$optionSet;
                unset($options);
            }else if( !empty($fieldOptionsResults) && is_string($fieldOptionsResults) ) {
				$option = array();
				$option["name"]=$fieldOptionsResults;
				$option["code"]=$fieldOptionsResults;
                $option["id"] ="o".substr(md5($option["name"]),0,10);
				$optionSet["externalAccess"]="false";
				$options[]=$option;
                $allOptions[]=$option;
				unset($option);
				$optionSet["options"]=$options;
                $optionSets[]=$optionSet;
                unset($options);
			}
            $dataElementOrgunitGroupset["optionSet"]=$optionSet;
            $dataElements[]=$dataElementOrgunitGroupset;
            unset($dataElementOrgunitGroupset);
        }

        // Form and Organisationunit name
        $dataElementOrgunitName = array();
        $dataElementOrgunitName["name"] =$hr_prefix. 'Organisationunit_name';
        $dataElementOrgunitName["code"] = substr(str_replace("_","",str_replace(" ","",'Organisationunit_name')),0,49);
        $dataElementOrgunitName["id"] = "OrgunitName";
        //$dataElementOrgunitName["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$dataElementOrgunitName["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //Inserting minimum dataElementInfo into programStageDataElement
        $programStageDataElement = array();
        $programStageDataElement["programStage"]=$humanResourceProgramStage;
        $programStageDataElement["dataElement"]=$dataElementOrgunitName;
        $programStageDataElement["compulsory"]= "false";
        $programStageDataElement["allowProvidedElsewhere"]= "false";
        $programStageDataElement["sortOrder"]= "0";
        $programStageDataElement["displayInReports"]= "false";
        $programStageDataElement["allowFutureDate"]= "false";
        $programStageDataElements[]=$programStageDataElement;
        //continue with preparting dataElement
        $dataElementOrgunitName["shortName"] = $hr_prefix.substr(str_replace("_","",str_replace(" ","",'Organisationunit_name')),0,49);
        $dataElementOrgunitName["domainType"]="TRACKER";
        $dataElementOrgunitName["aggregationOperator"]="sum";
        $dataElementOrgunitName["type"]="string";
        $dataElementOrgunitName["textType"]="text";
        //Place optionsets
        $optionSet = array();
        $optionSet["name"] =$dataElementOrgunitName["name"];
        $optionSet["code"] =substr(str_replace("_","",str_replace(" ","",'Organisationunit_name')),0,49);
        $optionSet["id"] ="o".substr(md5($optionSet["name"]),0,10);
        ///$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        $optionSet["externalAccess"]="false";
        $optionSet["publicAccess"]="rw------";
        $optionSet["version"]="1";
        $options = array();


        //Populate optionsets for dropdown fields//create the query to aggregate the records from the static resource table
        //check if field one is calculating field so to create the sub query
        $resourceTableName = ResourceTable::getStandardResourceTableName();
        $query="SELECT DISTINCT ResourceTable.".'Organisationunit_name'." FROM ".$resourceTableName." ResourceTable";
        $fieldOptionsResults = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $fieldOptionsResults = $resourceTableEntity->array_value_recursive(strtolower('Organisationunit_name'),$fieldOptionsResults);
        if(!empty($fieldOptionsResults) && is_array($fieldOptionsResults)) {
            foreach($fieldOptionsResults as $fieldOptionsResultKey=>$fieldOptionsResult) {
                $option = array();
                if(!empty($fieldOptionsResult)) {
                    $option["name"]=$fieldOptionsResult;
                    $option["code"]=$fieldOptionsResult;
                    $option["id"] ="o".substr(md5($option["name"]),0,10);
                    //$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                    //$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                    $optionSet["externalAccess"]="false";
                    $options[]=$option;
                    $allOptions[]=$option;
                    unset($option);
                }
            }
            $optionSet["options"]=$options;
            $optionSets[]=$optionSet;
            unset($options);
        }else if ( !empty($fieldOptionsResults) && is_string($fieldOptionsResults) ) {
			$option = array();
			$option["name"]=$fieldOptionsResults;
			$option["code"]=$fieldOptionsResults;
            $option["id"] ="o".substr(md5($option["name"]),0,10);
			$optionSet["externalAccess"]="false";
			$options[]=$option;
            $allOptions[]=$option;
			unset($option);
			$optionSet["options"]=$options;
            $optionSets[]=$optionSet;
            unset($options);
		}
        $dataElementOrgunitName["optionSet"]=$optionSet;
        $dataElements[]=$dataElementOrgunitName;
        unset($dataElementOrgunitName);
        $dataElementHrhFormName = array();
        $dataElementHrhFormName["name"] =$hr_prefix. 'Form_name';
        $dataElementHrhFormName["code"] = substr(str_replace("_","",str_replace(" ","",'Form_name')),0,49);
        $dataElementHrhFormName["id"] = "HrhFormName";
        //$dataElementHrhFormName["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$dataElementHrhFormName["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //Inserting minimum dataElementInfo into programStageDataElement
        $programStageDataElement = array();
        $programStageDataElement["programStage"]=$humanResourceProgramStage;
        $programStageDataElement["dataElement"]=$dataElementHrhFormName;
        $programStageDataElement["compulsory"]= "false";
        $programStageDataElement["allowProvidedElsewhere"]= "false";
        $programStageDataElement["sortOrder"]= "0";
        $programStageDataElement["displayInReports"]= "false";
        $programStageDataElement["allowFutureDate"]= "false";
        $programStageDataElements[]=$programStageDataElement;
        //continue with preparting dataElement
        $dataElementHrhFormName["shortName"] = $hr_prefix.'Form_name';
        $dataElementHrhFormName["domainType"]="TRACKER";
        $dataElementHrhFormName["aggregationOperator"]="sum";
        $dataElementHrhFormName["type"]="string";
        $dataElementHrhFormName["textType"]="text";
        //Place optionsets
        $optionSet = array();
        $optionSet["name"] =$dataElementHrhFormName["name"];
        $optionSet["code"] =substr(str_replace("_","",str_replace(" ","",$dataElementHrhFormName["name"])),0,49);
        $optionSet["id"] ="o".substr(md5($optionSet["name"]),0,10);
        //$optionSet["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$optionSet["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        $optionSet["externalAccess"]="false";
        $optionSet["publicAccess"]="rw------";
        $optionSet["version"]="1";
        $options = array();


        //Populate optionsets for dropdown fields//create the query to aggregate the records from the static resource table
        //check if field one is calculating field so to create the sub query
        $resourceTableName = ResourceTable::getStandardResourceTableName();
        $query="SELECT DISTINCT ResourceTable.".'Form_name'." FROM ".$resourceTableName." ResourceTable";
        $fieldOptionsResults = $em -> getConnection() -> executeQuery($query) -> fetchAll();
        $fieldOptionsResults = $resourceTableEntity->array_value_recursive(strtolower('Form_name'),$fieldOptionsResults);
        if(!empty($fieldOptionsResults) && is_array($fieldOptionsResults)) {
            foreach($fieldOptionsResults as $fieldOptionsResultKey=>$fieldOptionsResult) {
                $option = array();
                if(!empty($fieldOptionsResult)) {
                    $option["name"]=$fieldOptionsResult;
                    $option["code"]=$fieldOptionsResult;
                    $option["id"] ="o".substr(md5($option["name"]),0,10);
                    //$option["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                    //$option["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
                    $option["externalAccess"]="false";
                    $options[]=$option;
                    $allOptions[]=$option;
                    unset($option);
                }
            }
            $optionSet["options"]=$options;
            $optionSets[]=$optionSet;
            unset($options);
        }else if( !empty($fieldOptionsResults) && is_string($fieldOptionsResults) ) {
			$option = array();
			$option["name"]=$fieldOptionsResults;
			$option["code"]=$fieldOptionsResults;
            $option["id"] ="o".substr(md5($option["name"]),0,10);
			$option["externalAccess"]="false";
			$options[]=$option;
            $allOptions[]=$option;
			unset($option);
			$optionSet["options"]=$options;
            $optionSets[]=$optionSet;
            unset($options);
		}
        $dataElementHrhFormName["optionSet"]=$optionSet;
        $dataElements[]=$dataElementHrhFormName;
        unset($dataElementHrhFormName);

        $dataElementInstance = array();
        $dataElementInstance["name"] =$hr_prefix. 'instance';
        $dataElementInstance["code"] = substr(str_replace("_","",str_replace(" ","",'instance')),0,49);
        $dataElementInstance["id"] = "recInstance";
        //$dataElementInstance["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$dataElementInstance["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //Inserting minimum dataElementInfo into programStageDataElement
        $programStageDataElement = array();
        $programStageDataElement["programStage"]=$humanResourceProgramStage;
        $programStageDataElement["dataElement"]=$dataElementInstance;
        $programStageDataElement["compulsory"]= "false";
        $programStageDataElement["allowProvidedElsewhere"]= "false";
        $programStageDataElement["sortOrder"]= "0";
        $programStageDataElement["displayInReports"]= "false";
        $programStageDataElement["allowFutureDate"]= "false";
        $programStageDataElements[]=$programStageDataElement;
        //continue with preparting dataElement
        $dataElementInstance["shortName"] = $hr_prefix.'instance';
        $dataElementInstance["domainType"]="TRACKER";
        $dataElementInstance["aggregationOperator"]="sum";
        $dataElementInstance["type"]="string";
        $dataElementInstance["textType"]="text";
        $dataElements[]=$dataElementInstance;
        unset($dataElementInstanc);

        $metaData["dataElements"]=$dataElements;

        //Setting up program stages
        $programStages = array();
        $completeProgramStage = $humanResourceProgramStage;
        $completeProgramStage["minDaysFromStart"]=0;
        $completeProgramStage["program"]=$humanResourceProgram;
        $completeProgramStage["programStageDataElements"]=$programStageDataElements;
        $completeProgramStage["reportDateDescription"]= "Report date";
        $completeProgramStage["autoGenerateEvent"]= "true";
        $completeProgramStage["validCompleteOnly"]= "false";
        $completeProgramStage["displayGenerateEventBox"]= "false";
        $completeProgramStage["captureCoordinates"]= "false";
        $completeProgramStage["blockEntryForm"]= "false";
        $completeProgramStage["preGenerateUID"]= "false";
        $completeProgramStage["remindCompleted"]= "false";
        $completeProgramStage["generatedByEnrollmentDate"]= "false";
        $completeProgramStage["allowGenerateNextVisit"]= "false";
        $completeProgramStage["openAfterEnrollment"]= "false";
        $completeProgramStage["dataEntryType"]= "default";
        $completeProgramStage["defaultTemplateMessage"]= "Dear {person-name} please come to your appointment on {program-stage-name} at {due-date}";
        $completeProgramStage["id"]= "NHIu1snluiZ";
        $completeProgramStage["repeatable"]= "false";
        $programStages[]=$completeProgramStage;
        $metaData["programStages"]=$programStages;


        $programs = array();
        $humanResourceProgram["name"] =$hr_prefix."HumanResource";
        //$humanResourceProgram["created"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        //$humanResourceProgram["lastUpdated"]=(new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');
        $humanResourceProgram["description"]="Raw Data from HRH Database";
        $humanResourceProgram["version"]="1";
        $humanResourceProgram["version"]= 1;
        $humanResourceProgram["dateOfEnrollmentDescription"]= "Enrollment Date";
        $humanResourceProgram["dateOfIncidentDescription"]= "Incident Date";
        $humanResourceProgram["type"]= 3;
        $humanResourceProgram["displayIncidentDate"]= false;
        $humanResourceProgram["ignoreOverdueEvents"]= false;
        $humanResourceProgram["onlyEnrollOnce"]= false;
        $humanResourceProgram["selectEnrollmentDatesInFuture"]= false;
        $humanResourceProgram["selectIncidentDatesInFuture"]= false;
        $humanResourceProgram["relationshipText"]= "";
        $humanResourceProgram["dataEntryMethod"]= false;
        $humanResourceProgram["registration"]= false;
        $humanResourceProgram["singleEvent"]= true;
        $humanResourceProgram["kind"]= "SINGLE_EVENT_WITHOUT_REGISTRATION";
        $humanResourceProgram["programStages"]=array($humanResourceProgramStage);
        $programs[]=$humanResourceProgram;
        $metaData["programs"]=$programs;
        $metaData["options"]=$allOptions;
        $metaData["optionSets"]=$optionSets;

        $serializer = new Serializer(array($normalizer), array($encoder));
        $outputMetadata = $serializer->serialize($metaData, $this->resourceTableNature); // Output: {"name":"foo","sportsman":false}

        $this->messageLog = $outputMetadata;
        $output->writeln($this->messageLog);
    }

    /**
     * @var string
     */
    private $resourceTableName;

    /**
     * @var string
     */
    private $resourceTableNature;

    /**
     * @var object
     */
    private $em;

    /**
     * @var array
     */
    private $metaData;

    /**
     * @var array
     */
    private $customDropDown;

    /**
     * @var string
     */
    private $messageLog;
}
