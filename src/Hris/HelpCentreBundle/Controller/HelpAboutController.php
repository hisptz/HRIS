<?php

namespace Hris\HelpCentreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Finder\Finder;

/**
 * HelpAbout controller.
 *
 * @Route("/help/about")
 */
class HelpAboutController extends Controller
{

    /**
     * Help about information.
     *
     * @Secure(roles="ROLE_SUPER_USER,ROLE_HELPCENTRE_ABOUT,ROLE_USER")
     * @Route("/", name="help_helpabout")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $git_repo = $this->get('kernel')->getRootDir().'/../.git/refs/heads';

        $finder = new Finder();
        $finder->in($git_repo);
        $finder->files()->name('master');

        foreach($finder as $file) {
            $commit = $file->getContents();
        }
        $useragent = explode(') ',str_replace(') ',')) ',$this->getRequest()->headers->get('user-agent'))) ;

        return array(
            'commit' => $commit,
            'useragent'=>$useragent,
        );
    }

}
