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
namespace Hris\RecordsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Common\Annotations\True;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\QueryBuilder as QueryBuilder;
use FOS\UserBundle\Doctrine;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator  as DoctrineHydrator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hris\RecordsBundle\Entity\Record;
use Hris\RecordsBundle\Form\RecordType;
use Hris\OrganisationunitBundle\Entity\Organisationunit;
use Doctrine\Common\Collections\ArrayCollection;
use Hris\FormBundle\Entity\Field;
use Hris\FormBundle\Form\FormType;
use Hris\FormBundle\Form\DesignFormType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use JMS\SecurityExtraBundle\Annotation\Secure;
use DateTime;

class ImportFromExcelCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('hris:records:import')
            ->setDescription('Import records from excel document for columns existing in the database')
            ->addArgument('file',InputArgument::OPTIONAL,'Path of Excel File to Import')
            ->addArgument('form',InputArgument::OPTIONAL,'Data Entry Form Name')
            ->addArgument('organisationunit',InputArgument::OPTIONAL, 'Name of organization unit to store data on')
            ->setHelp(<<<EOT
The <info>hris:records:import</info> command imports data from excel sheet into the database for columns existing in database

  <info>php app/console hris:records:import</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $filePath = $input->getArgument('file');
        $dataEntryFormName = $input->getArgument('form');
        $organisationunitName = $input->getArgument('organisationunit');

        $em = $this->getContainer()->get('doctrine');

        $formEntity = $em->getRepository('HrisFormBundle:Form')->findOneBy(array('name'=>$dataEntryFormName));

        $fields = $formEntity->getSimpleField();

        $phpExcelObject = $this->getContainer()->get('phpexcel')->createPHPExcelObject($filePath);
        $objWorksheet = $phpExcelObject->getActiveSheet();
        $i=0;
        $fieldUIDs = array();
        $fieldObjects = array();
        foreach ($objWorksheet->getRowIterator() as $row) {
            //Parse the headings on first row for deducing equivalent fields in database
            if($i < 1){
                $cellIterator = $row->getCellIterator();
                $k = 1;
                foreach ($cellIterator as $cell) {
                    $field = $em->getRepository('HrisFormBundle:Field')->findOneBy(array('name'=>$cell->getValue()));
                    if(!empty($field)) {
                        // Construct array of columns with matched Fields
                        $fieldUIDs[$k] = $field->getUid();
                        $fieldObjects[$k] = $field;
                        $k++;
                    }
                }

            }else {
                break; //@todo placed to avoid going through many rows
            }
            $i++;
        }
        // Construct data values array
        $dataValueArray = array();
        $j = 1;
        foreach ($objWorksheet->getRowIterator() as $row) {
            //if($j > 1 && $j < 7){
                $cellIterator = $row->getCellIterator();
                $k = 1;
                $instancestring= "";
                //$instance=md5($firstName.$middleName.$surname.$dateOfBirth->format('Y-m-d'));
                foreach ($cellIterator as $cell) {
                    if($k==2 || $k==3 || $k==4){
                        $instancestring.=$cell->getValue().uniqid();
                    }
                    if($k==5){
                        $instancestring.=$cell->getValue().uniqid();
                    }
                    $cellIntVal = intval($cell->getValue());
                    if(isset($fieldObjects[$k]) && !empty($fieldObjects[$k])) {
                        if($fieldObjects[$k]->getDataType()->getName() == "Date" && $cellIntVal!=0 ){

                            $year=substr($cell->getValue(),0,4);
                            $month=substr($cell->getValue(),4,2);
                            $days=substr($cell->getValue(),6,2);
                            $formattedDate=$year."-".$month."-".$days;
                            $dataValueArray[$fieldUIDs[$k]] = new \DateTime($formattedDate);
                        }elseif($fieldObjects[$k]->getInputType()->getName() == "Select"){
                            //special check for sex
                            if($fieldObjects[$k]->getName() == "sex"){
                                foreach($fieldObjects[$k]->getFieldOption() as $option){
                                    $val = ($cell->getValue()== "M")?"Male":"Female";
                                    if($option->getValue() == $val){
                                        $dataValueArray[$fieldUIDs[$k]] = $option->getUid();
                                    }
                                }
                            }elseif($fieldObjects[$k]->getName() == "Religion"){

                                foreach($fieldObjects[$k]->getFieldOption() as $option){
                                    if(strtolower($option->getValue()) == strtolower($cell->getValue())){
                                        $dataValueArray[$fieldUIDs[$k]] = $option->getUid();
                                    }

                                }
                            }else{
                                foreach($fieldObjects[$k]->getFieldOption() as $option){
                                    if($option->getValue() == $cell->getValue()){
                                        $dataValueArray[$fieldUIDs[$k]] = $option->getUid();
                                    }
                                }
                            }

                        }else{
                            $dataValueArray[$fieldUIDs[$k]] = $cell->getValue();
                        }
                    }
                    $k++;


                }
                $instance=md5($instancestring);
                    //for basic education level
                    $dataValueArray["5289e93496216"] = "5289e93871f64";

                // for employment_status
                    $dataValueArray["5289e934a6b16"] = "5289e934f353d";

                //for employer
                       $dataValueArray["5289e934a59a6"] = "528a0ae3249d2";
                $orgunit = $em->getRepository('HrisOrganisationunitBundle:Organisationunit')->findOneBy(array('longname' => $organisationunitName));
                $entity = new Record();
                $entity->setValue($dataValueArray);
                $entity->setForm($formEntity);
                $entity->setInstance($instance);
                $entity->setOrganisationunit($orgunit);
                $entity->setUsername("admin");
                $entity->setComplete(True);
                $entity->setCorrect(True);
                $entity->setHashistory(False);
                $entity->setHastraining(False);

                $em->getManager()->persist($entity);
                $em->getManager()->flush();

            }
            //$j++;
        //}


    }
}