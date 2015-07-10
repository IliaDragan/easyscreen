<?php

namespace Inlead\Easyscreen\SearchBundle\Controller;

/*
Dummy ALMA: http://dummy-alma.inlead.dk/web/alma/
*/
define("ALMA_BASE_URL", "https://roar.roskildebib.dk:8070/alma/");

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Inlead\Easyscreen\SearchBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Inlead\Easyscreen\SearchBundle\AlmaClient\AlmaClient as AlmaClient;

class ProviderController extends Controller {
  public function availabilityAction(Request $request) {
    $items = explode(',', $request->query->get('items'));

    $provider = new AlmaClient(ALMA_BASE_URL);
    $availability = $provider->get_availability(implode(',', $items));
    $xml = new \SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?><availability></availability>');
    foreach ($availability as $k=>$v) {
      $item = $xml->addChild('item');
      $item->addChild('id', $k);
      $item->addChild('status', $v['available'] ? 1 : 0);
    }
    $response = new Response($xml->asXML());
    return $response;
  }

} 