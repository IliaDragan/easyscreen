<?php

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function searchAction()
    {




$response = new Response(json_encode(array('name' => 'aaaa')));
$response->headers->set('Content-Type', 'application/xml');

return $response;




        /*return $this->render('InleadEasyscreenSearchBundle:Search:search.html.twig', array(
                // ...
            )); */   }

    public function singleAction()
    {
        return $this->render('InleadEasyscreenSearchBundle:Search:single.html.twig', array(
                // ...
            ));    }

}
