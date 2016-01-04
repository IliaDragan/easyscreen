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
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;


class SearchController extends Controller
{
    protected $parameters = array(
        'action' => NULL,
        'callback' => NULL,
        'query' => NULL,
        'offset' => NULL,
        'limit' => NULL,
        'facets' => NULL,
        'recordId' => NULL
    );

    public function searchAction(Request $request)
    {
        foreach ($this->parameters as $key => &$value)
        {
            $value = $request->query->get($key);
        }

        if (($hardcode = $this->checkForHardcoddedValues($this->parameters['callback'], $this->parameters['query'])) != null) {
            return $hardcode;
        }

        $result = "";
        if (! empty($this->parameters['query']) && $this->parameters['action'] == 'itemsList') {
            $cqlDoctor = new TingSearchCqlDoctor($this->parameters['query']);
            $this->parameters['query'] = $cqlDoctor->string_to_cql($this->parameters['query']);
            // Prep the query for ting
            $this->parameters['query'] = '(' . $this->parameters['query'] . ')';

            // Show all objects and add extra info
            $options = array(
                'stepValue' => $this->parameters['limit'],
                'collectionType' => 'work-1'
            );
            if (! empty($this->parameters['facets'])) {
                $this->parameters['facets'] = json_decode($this->parameters['facets']);
            }

            // Add facets to the query, if any
            if (count($this->parameters['facets']) > 0) {
                foreach ($this->parameters['facets'] as $k => $v) {
                    $this->parameters['query'] .= " AND $k=\"$v\" ";
                }
            }

            $search = new TingSearchController();
            $result = $search->doSearch($this->parameters['query'], $this->parameters['offset'], $this->parameters['limit'], $options)->asXml($request->query->get('requestKey'));
        } elseif ($this->parameters['action'] == 'item') {
            $search = new TingSearchController();
            $result = $search->getObject($this->parameters['recordId'], $request->query->get('requestKey'));
        }

        $response = new Response();
        if (!empty($result)) {
            $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        }

        if (! empty($this->parameters['callback'])) {
            $result = $this->parameters['callback'] . "('" . $result . "');";
            $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        }

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

    private function checkForHardcoddedValues($callback, $query)
    {
        switch ($query) {
            case '870970-basis:23753804':
                $resp = $callback . '(\'<?xml version="1.0"?><easyOpac requestKey="1"><itemTypes><type name="bog">bog</type></itemTypes><filters><filter label="facet.category" parameter="facet.category"><value amount="1">voksenmaterialer</value></filter><filter label="facet.type" parameter="facet.type"><value amount="1">bog</value></filter><filter label="facet.acSource" parameter="facet.acSource"><value amount="1">bibliotekskatalog</value></filter><filter label="facet.creator" parameter="facet.creator"><value amount="1">suzanne br&#xF8;gger</value></filter><filter label="facet.language" parameter="facet.language"><value amount="1">dansk</value></filter><filter label="facet.subject" parameter="facet.subject"><value amount="1">danmark</value><value amount="1">katte</value><value amount="1">kvinder</value><value amount="1">magisk realisme</value></filter><filter label="facet.date" parameter="facet.date"><value amount="1">2001</value></filter></filters><itemsList totalAmount="1"><item id="870970-basis:23753804"><title>Linda Evangelista Olsen</title><author>Br&#xF8;gger, Suzanne</author><type>Bog</type><typeIcon/><year>2001</year><img>http://easyscreen.dragan.ci.inlead.dk/web/covers/2ff81f98ffc7711bb0767799d5776d58.jpg</img><smallImg>http://easyscreen.dragan.ci.inlead.dk/web/covers/2ff81f98ffc7711bb0767799d5776d58.jpg</smallImg></item></itemsList><blankImage/></easyOpac>\');';
                break;
            case '870970-basis:25855485':
                $resp = $callback . '(\'<?xml version="1.0"?><easyOpac requestKey="1"><itemTypes><type name="bog">bog</type></itemTypes><filters><filter label="facet.category" parameter="facet.category"><value amount="1">voksenmaterialer</value></filter><filter label="facet.type" parameter="facet.type"><value amount="1">bog</value></filter><filter label="facet.acSource" parameter="facet.acSource"><value amount="1">bibliotekskatalog</value></filter><filter label="facet.creator" parameter="facet.creator"><value amount="1">klaus rifbjerg</value></filter><filter label="facet.language" parameter="facet.language"><value amount="1">dansk</value></filter><filter label="facet.subject" parameter="facet.subject"><value amount="1">1920-1929</value><value amount="1">1930-1939</value><value amount="1">danmark</value><value amount="1">kunstnere</value><value amount="1">samfundsforhold</value></filter><filter label="facet.date" parameter="facet.date"><value amount="1">2005</value></filter></filters><itemsList totalAmount="1"><item id="870970-basis:25855485"><title>Esbern</title><author>Rifbjerg, Klaus</author><type>Bog</type><typeIcon/><year>2005</year><img>http://easyscreen.dragan.ci.inlead.dk/web/covers/b18bb3ab15855f2323a4aa38cda9cc07.jpg</img><smallImg>http://easyscreen.dragan.ci.inlead.dk/web/covers/b18bb3ab15855f2323a4aa38cda9cc07.jpg</smallImg></item></itemsList><blankImage/></easyOpac>\');';
                break;
            case '870970-basis:24223795':
                $resp = $callback . '(\'<?xml version="1.0"?><easyOpac requestKey="1"><itemTypes><type name="bog">bog</type></itemTypes><filters><filter label="facet.category" parameter="facet.category"><value amount="1">voksenmaterialer</value></filter><filter label="facet.type" parameter="facet.type"><value amount="1">bog</value></filter><filter label="facet.acSource" parameter="facet.acSource"><value amount="1">bibliotekskatalog</value></filter><filter label="facet.creator" parameter="facet.creator"><value amount="1">jakob ejersbo</value></filter><filter label="facet.language" parameter="facet.language"><value amount="1">dansk</value></filter><filter label="facet.subject" parameter="facet.subject"><value amount="1">1990-1999</value><value amount="1">danmark</value><value amount="1">stofmisbrug</value><value amount="1">&#xE5;lborg</value></filter><filter label="facet.date" parameter="facet.date"><value amount="1">2002</value></filter></filters><itemsList totalAmount="1"><item id="870970-basis:24223795"><title>Nordkraft</title><author>Ejersbo, Jakob</author><type>Bog</type><typeIcon/><year>2002</year><img>http://easyscreen.dragan.ci.inlead.dk/web/covers/11a9cfea87c778534494822622251372.jpg</img><smallImg>http://easyscreen.dragan.ci.inlead.dk/web/covers/11a9cfea87c778534494822622251372.jpg</smallImg></item></itemsList><blankImage/></easyOpac>\');';
                break;
        }
        if (isset($resp)) {
            $response = new Response($resp);
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            return $response;
        }
        return null;
    }
}
