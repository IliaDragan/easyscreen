<?php

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Inlead\Easyscreen\SearchBundle;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;


class SearchController extends Controller
{
    public function searchAction(Request $request)
    {
      $query = $request->query->get('query');
      $offset = intval($request->query->get('offset'));
      $limit = intval($request->query->get('limit'));
      $facets = $request->query->get('facets');
      $faust = $request->query->get('recordId');
      $result = "";
      if (!empty($query) && $request->query->get('action') == 'itemsList') {

        // Prep the query for ting
        $query = '(' . $query . ')';

        // Show all objects and add extra info
        $options = array(
          'stepValue' => '20',
          'collectionType' => 'work-1',
        );
        if (!empty($facets)) {
          $facets = json_decode($facets);
        }

        // Add facets to the query, if any
        if (count($facets) > 0) {
          foreach ($facets as $k => $v) {
            $query .= " AND $k=\"$v\" ";
          }
        }

        $search = new TingSearchController();
        $result = $search->doSearch($query, $offset, $limit, $options)->asXml($request->query->get('requestKey'));
      } elseif($request->query->get('action') == 'item') {
        $search = new TingSearchController();
        $result = $search->getObject($faust, $request->query->get('requestKey'));
      }

      $callback = $request->query->get('callback');
      if (empty($callback)) {
        $callback = empty($_REQUEST['callback']) ? 'jsonpCallback' : $_REQUEST['callback'];
      }
      $callback = preg_replace('/[^\w]/', '', $callback);

      $result = str_replace(array("\n"), '', $result);
      $result = "'" . str_replace("'", "\'", $result) . "'";

      $result = $callback . "(" . $result . ");";

      $response = new Response($result);
      $response->headers->set('Content-Type', 'text/html; charset=UTF-8');


      return $response;
    }

    public function singleAction()
    {
        return $this->render('InleadEasyscreenSearchBundle:Search:single.html.twig', array(
                // ...
            ));    }

}
