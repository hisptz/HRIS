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

class ExportDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hris:export:data')
            ->setDescription('Generate events data')
            ->addArgument('year', InputArgument::OPTIONAL, 'Year of analysis')
            ->addArgument('name', InputArgument::OPTIONAL, 'Resourcetable name')
            ->addOption('json', null, InputOption::VALUE_NONE, 'If set, then output will be in json format')
            ->addOption('xml', null, InputOption::VALUE_NONE,'If set, then output will be in xml format')
            ->addOption('csv', null, InputOption::VALUE_NONE,'If set, then output will be in csv format')
            ->setHelp(<<<EOT
The <info>hris:export:metadata</info> command generates metadata for human resource

  <info>php app/console hris:export:metadata</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $capturedYear = $input->getArgument('year');
        if(!empty($capturedYear)) {
            $year= $capturedYear;
        }else {
            $year = (date("Y")-1);
        }
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
        $sensitiveFields = array("firstname","middlename","surname","file_no","reg_no","check_no","salary","religion","next_kin","contact_of_next_of_kin");

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
        }elseif($input->getOption('csv')) {
            $this->resourceTableNature = 'csv';
        }else {
            $this->resourceTableNature = 'json';
            $encoder = new JsonEncoder();
        }

		$filename="events";
		$destination="/tmp/";
        $csvFile = $destination.$filename."_".$year.".csv";

        //$normalizer->setIgnoredAttributes(array('age'));

        $metaData = array();
        $metaData["created"] = (new \DateTime('now'))->format('Y-m-d\TH:i:s.000O');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $logger = $this->getContainer()->get('logger');

        // Find Resource table to know existing fields in the genreated table
        $resourceTableEntity = $em->getRepository('HrisFormBundle:ResourceTable')->findOneBy(array('name'=>$this->resourceTableName));



        $resourceTableName = ResourceTable::getStandardResourceTableName();
        $query="SELECT ResourceTable.*,hris_organisationunit.dhisuid FROM ".$resourceTableName." ResourceTable INNER JOIN hris_organisationunit ON hris_organisationunit.id= ResourceTable.organisationunit_id WHERE hris_organisationunit.dhisuid IS NOT NULL AND ResourceTable.first_appointment_year<=".$year." AND ResourceTable.retirementdate_year>=".$year." AND ResourceTable.employment_status !='Resigned' AND ResourceTable.employment_status !='Deceased' AND ResourceTable.employment_status !='Retired' AND ResourceTable.employment_status !='Abscondent' AND ResourceTable.employment_status !='Off Duty'";
        echo $query;
        echo "\n";
        $hrRecords = $em -> getConnection() -> executeQuery($query) -> fetchAll();

        $csvContents = "event,status,program,programStage,enrollment,orgUnit,eventDate,dueDate,latitude,longitude,dataElement,value,storedBy,providedElsewhere\n";


        file_put_contents($csvFile,$csvContents);
        unset($csvContents);

        foreach($hrRecords as $hrRecordkey=>$hrRecord) {
            $csvContents='';
            //For each record field go through the resource table field columns and put the record values
            foreach($resourceTableEntity->getResourceTableFieldMember() as $resourceTableKey=> $resourceTableFieldMember) {
                $field = $resourceTableFieldMember->getField();
				//Sensitive fields to skip
				if(in_array(strtolower($field->getName()),$sensitiveFields)) continue;
				//If no value stored, no need to bulk up csv export
				if(empty(trim($hrRecord[strtolower($field->getName())]))) continue;

                //Fill in value for a data row
                $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                    .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                    .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                    ."F".substr($field->getUid(),3).","
                    ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName())])."\"".",mukulu,false"."\n";

                if($field->getDataType()->getName() == "Date") {

                    $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                        .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                        .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                        ."Fmt".substr($field->getUid(),5).","
                        ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName()).'_month_text'])."\"".",mukulu,false"."\n";

                    $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                        .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                        .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                        ."Fyr".substr($field->getUid(),5).","
                        ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName()).'_year'])."\"".",mukulu,false"."\n";
                }

                if($field->getHashistory()) {
                    $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                        .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                        .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                        ."Flu".substr($field->getUid(),5).","
                        ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName()).'_last_updated'])."\"".",mukulu,false"."\n";

                    $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                        .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                        .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                        ."Fhmt".substr($field->getUid(),6).","
                        ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName()).'_last_updated_month_text'])."\"".",mukulu,false"."\n";

                    $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                        .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                        .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                        ."Fhyr".substr($field->getUid(),6).","
                        ."\"".str_replace("\"","`",$hrRecord[strtolower($field->getName()).'_last_updated_year'])."\"".",mukulu,false"."\n";
                }

            }
            //Go through each record organsationunit level columns
            $organisationunitLevels = $em->createQuery('SELECT DISTINCT organisationunitLevel FROM HrisOrganisationunitBundle:OrganisationunitLevel organisationunitLevel ORDER BY organisationunitLevel.level ')->getResult();
            foreach($organisationunitLevels as $organisationunitLevelKey=>$organisationunitLevel) {

                $organisationunitLevelName = "level".$organisationunitLevel->getLevel()."_".str_replace(',','_',str_replace('.','_',str_replace('/','_',str_replace(' ','_',$organisationunitLevel->getName())))) ;
                $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                    .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                    .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                    ."Lvl".$organisationunitLevel->getLevel().substr($organisationunitLevel->getUid(),6).","
                    ."\"".str_replace("\"","`",$hrRecord[strtolower($organisationunitLevelName)])."\"".",mukulu,false"."\n";

            }
            // Go through OrganisationunitGroupsets Column
            $organisationunitGroupsets = $em->getRepository('HrisOrganisationunitBundle:OrganisationunitGroupset')->findAll();
            foreach($organisationunitGroupsets as $organisationunitGroupsetKey=>$organisationunitGroupset) {
                $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                    .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                    .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                    ."Grp".substr($organisationunitGroupset->getUid(),5).","
                    ."\"".str_replace("\"","`",$hrRecord[strtolower($organisationunitGroupset->getName())])."\"".",mukulu,false"."\n";
            }

            $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                ."OrgunitName".","
                ."\"".str_replace("\"","`",$hrRecord[strtolower('Organisationunit_name')])."\"".",mukulu,false"."\n";

            $csvContents.="I".substr($hrRecord["instance"],strlen($hrRecord["instance"])-10)
                .",ACTIVE,Gcghe0W76Ms,NHIu1snluiZ,,"
                .$hrRecord["dhisuid"].",".$year.substr($hrRecord["lastupdated"],4,strlen($hrRecord["lastupdated"])).",,,,"
                ."HrhFormName".","
                ."\"".str_replace("\"","`",$hrRecord[strtolower('Form_name')])."\"".",mukulu,false"."\n";

            file_put_contents($csvFile,$csvContents,FILE_APPEND);
            unset($csvContents);
        }
        $output->writeln('Done creating csv!!');
        
        //Outputfile
        $gzfile=$csvFile.".gz";
        //Open the gz file with w9 highest compression
        $fp = gzopen($gzfile,'w9');
        //Compress the file
        gzwrite($fp, file_get_contents($csvFile));
        //Close the gz file
        gzclose($fp);
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
