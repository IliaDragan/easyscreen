<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Inlead\Easyscreen\SearchBundle\Utils\FbsProvider;
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

        $provider = new FbsProvider();
        $availability = $provider->getAvailability($items);

        $xml = new \SimpleXmlElement('<availability />');
        foreach ($availability as $k => $v) {
            $item = $xml->addChild('item');
            $item->addChild('id', $k);
            $item->addChild('status', (int) $v['available']);
        }

        $result = preg_replace('/[\n\r]/', '', $xml->asXML());
        $response = new Response($result);
        $response->headers->set('Content-type', 'text/xml');

        return $response;
    }

    public function branchesAction()
    {
        $provider = new FbsProvider();
        $branches = $provider->getReservationBranches();

        $xml = new \SimpleXmlElement('<branches />');
        foreach ($branches as $id => $name) {
            if (empty($name)) {
                continue;
            }
            $branch = $xml->addChild('branch');
            $branch->addChild('id', $id);
            $branch->addChild('name', htmlspecialchars($name));
        }

        $result = preg_replace('/[\n\r]/', '', $xml->asXML());
        $response = new Response($result);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}
