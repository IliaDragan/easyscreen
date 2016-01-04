<?php
namespace Inlead\Easyscreen\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{

    public function indexAction()
    {
        return $this->render('InleadEasyscreenSearchBundle:Default:index.html.twig');
    }
}
