<?php

namespace Hris\LeaveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('HrisLeaveBundle:Default:index.html.twig', array('name' => $name));
    }
}
