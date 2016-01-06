<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Inlead\Easyscreen\SearchBundle\Utils\AlmaProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ProviderController extends Controller
{
    public function availabilityAction(Request $request)
    {
        if (empty($request->query->get('items'))) {
            throw new \Exception('No "items" parameter supplied.');
        }

        $items = explode(',', $request->query->get('items'));

        $provider = new AlmaProvider();
        $availability = $provider->getAvailability($items);

        $xml = new \SimpleXmlElement('<availability />');
        foreach ($availability as $k => $v) {
            $item = $xml->addChild('item');
            $item->addChild('id', $k);
            $item->addChild('status', (int) $v['available']);
        }

        $response = new Response($xml->asXML());
        $response->headers->set('Content-type', 'text/xml');

        return $response;
    }

    public function branchesAction(Request $request)
    {
        $provider = new AlmaProvider();
        $branches = $provider->getReservationBranches();

        $xml = new \SimpleXmlElement('<branches />');
        foreach ($branches as $id => $name) {
            $branch = $xml->addChild('branch');
            $branch->addChild('id', $id);
            $branch->addChild('name', $name);
        }

        $response = new Response($xml->asXML());
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}
