<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Inlead\Easyscreen\SearchBundle\Utils\TingSearchCqlDoctor;
use Inlead\Easyscreen\SearchBundle\Utils\TingSearchController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    protected $parameters = array(
        'action' => null,
        'callback' => null,
        'query' => null,
        'offset' => null,
        'limit' => null,
        'facets' => null,
        'recordId' => null,
        'requestKey' => null,
        'branch' => null,
    );

    public function searchAction(Request $request)
    {
        foreach ($this->parameters as $key => &$value) {
            $value = $request->query->get($key);
        }

        $result = '';
        if (!empty($this->parameters['query']) && $this->parameters['action'] == 'itemsList') {
            $cqlDoctor = new TingSearchCqlDoctor($this->parameters['query']);
            $this->parameters['query'] = $cqlDoctor->string_to_cql($this->parameters['query']);
            // Prep the query for ting
            $this->parameters['query'] = '('.$this->parameters['query'].')';

            // Show all objects and add extra info
            $options = array(
                'stepValue' => $this->parameters['limit'],
                'collectionType' => 'work-1',
            );
            if (!empty($this->parameters['facets'])) {
                $this->parameters['facets'] = json_decode($this->parameters['facets']);
            }

            // Add facets to the query, if any.
            if (count($this->parameters['facets']) > 0) {
                foreach ($this->parameters['facets'] as $k => $v) {
                    $this->parameters['query'] .= " AND $k=\"$v\" ";
                }
            }

            $search = new TingSearchController();
            $result = $search->getSearchResult($this->parameters['query'], $this->parameters['offset'], $this->parameters['limit'], $options, $this->parameters['requestKey'], $this->parameters['branch']);
        } elseif ($this->parameters['action'] == 'item') {
            $search = new TingSearchController();
            $result = $search->getObject($this->parameters['recordId'], $this->parameters['requestKey']);
        }

        $response = new Response();
        if (!empty($result)) {
            $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        }

        if (!empty($this->parameters['callback'])) {
            $result = $this->parameters['callback']."('".$result."');";
            $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        }

        $result = preg_replace('/[\n\r]/', '', $result);
        $response->setContent($result);

        return $response;
    }

    public function departmentsAction()
    {
        $ting = new TingSearchController();
        $result = $ting->getDepartments();

        $response = new Response($result);
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }
}
