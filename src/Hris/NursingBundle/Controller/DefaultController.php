<?php

namespace Hris\NursingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('HrisNursingBundle:Default:index.html.twig', array('name' => $name));
    }
}
