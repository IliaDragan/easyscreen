<?php

namespace Inlead\Easyscreen\SearchBundle\Utils;

define('TING_SEARCH_URL','http://opensearch.addi.dk/4.0.1/');
define('TING_SCAN_URL', 'http://openscan.addi.dk/1.7/');
define('TING_SPELL_URL', 'http://openspell.addi.dk/1.2/');
define('TING_RECOMMENDATION_URL', 'http://openadhl.addi.dk/1.1/');
define('TING_AGENCY_ID', 733000);
define('TING_SEARCH_PROFILE', 'opac');
define('TING_INFOMEDIA_URL', 'http://useraccessinfomedia.addi.dk/1.1/');


use Inlead\Easyscreen\SearchBundle;

use Inlead\Easyscreen\SearchBundle\TingClient\lib\TingClient as TingClient;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\request\TingClientRequestFactory as TingClientRequestFactory;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\adapter\TingClientRequestAdapter as TingClientRequestAdapter;

class TingSearchController
{
  private $requestFactory;
  private $client;
  private $searchResult;
  private $facetbrowser =  array (
    0 =>
      array (
        'name' => 'facet.category',
        'title' => 'Børn/voksen',
        'sorting' => 'default',
        'weight' => '-10',
      ),
    1 =>
      array (
        'name' => 'facet.type',
        'title' => 'Materialetype',
        'sorting' => 'default',
        'weight' => '-9',
      ),
    2 =>
      array (
        'name' => 'facet.acSource',
        'title' => 'Kilde',
        'sorting' => 'default',
        'weight' => '-8',
      ),
    3 =>
      array (
        'name' => 'facet.creator',
        'title' => 'Forfatter',
        'sorting' => 'default',
        'weight' => '-7',
      ),
    4 =>
      array (
        'name' => 'facet.language',
        'title' => 'Sprog',
        'sorting' => 'default',
        'weight' => '-6',
      ),
    5 =>
      array (
        'name' => 'facet.subject',
        'title' => 'Emne',
        'sorting' => 'default',
        'weight' => '-5',
      ),
    6 =>
      array (
        'name' => 'facet.date',
        'title' => 'Årstal',
        'sorting' => 'numeric_reverse',
        'weight' => '-4',
      ),
  );


  public function doSearch($query, $offset, $results_per_page = 10, $options = array()) {
    $request = $this->getRequestFactory()->getSearchRequest();
    //var_dump($query);

    if (!is_object($request)) {
      return NULL;
    }
    $request->setQuery($query);
    $request->setAgency(TING_AGENCY_ID);

    $request->setStart($offset);
    $request->setNumResults($results_per_page);

    if (!isset($options['facets'])) {
      $options['facets'] = array();
      // Populate facets with configured facets.
      foreach ($this->facetbrowser as $facet) {
        $options['facets'][] = $facet['name'];
      }
    }

    $default_facets = array(
      'facet.subject',
      'facet.creator',
      'facet.type',
      'facet.category',
      'facet.language',
      'facet.date',
      'facet.acSource',
    );
    if (isset($options['stepValue'])) {
      $request->setNumResults($options['stepValue']);
    }
    $request->setFacets((isset($options['facets'])) ? $options['facets'] : $default_facets);
    $request->setNumFacets((isset($options['numFacets'])) ? $options['numFacets'] : ((count($request->getFacets()) == 0) ? 0 : 10));
    if (isset($options['sort']) && $options['sort']) {
      $request->setSort($options['sort']);
    }
    if (isset($options['collectionType'])) {
      $request->setCollectionType($options['collectionType']);
    }
    $request->setAllObjects(isset($options['allObjects']) ? $options['allObjects'] : FALSE);

    // Set search profile
    $request->setProfile(TING_SEARCH_PROFILE);

    $request->setRank('rank_general');

    $this->searchResult = $this->execute($request);

    return $this;
  }

  public function asXml($requestKey) {
    $xml = new \SimpleXmlElement('<?xml version=\'1.0\'?><easyOpac></easyOpac>');

    $xml->addAttribute('requestKey', $requestKey);

    // Create types
    $item_types = $xml->addChild('itemTypes');
    $result_types = array();

    if (is_object($this->searchResult))  {
      foreach ($this->searchResult->facets as $k => $v) {
        if ($v->name == 'facet.type') {
          foreach ($v->terms as $k => $v) {
            $result_types[] = $k;
          }
        }
      }
    }

    foreach ($result_types as $k => $v) {
      $type = $item_types->addChild('type', $v);
      $type->addAttribute('name', $v);
    }

    // Create filters (facets)
    $item_filters = $xml->addChild('filters');

    if (is_object($this->searchResult)) {
      foreach ($this->searchResult->facets as $k => $v) {
        $filter = $item_filters->addChild('filter');
        $filter->addAttribute('label', $v->name);
        $filter->addAttribute('parameter', $v->name);

        foreach ($v->terms as $kk => $vv) {
          $value = $filter->addChild('value', htmlspecialchars($kk));
          $value->addAttribute('amount', $vv);
        }
      }
    }

    $item_list = $xml->addChild('itemsList');
    $item_list->addAttribute('totalAmount', $this->searchResult->numTotalObjects);

    // Loop through every collection and object in it
    if (is_object($this->searchResult)) {
      $faustNumbers = array();
      foreach ($this->searchResult->collections as $v) {
        foreach ($v->objects as $vv) {
          if (isset($vv->localId)) {
            $faustNumbers[] = $vv->localId;
          }
        }
      }

      $images = new CoverImageController();
      $images = $images->getCoverImage($faustNumbers);
      foreach ($this->searchResult->collections as $v) {
        $object = $v->objects[0];
        try {
          $title = $object->record['dc:title'][''][0];
        }
        catch (\Exception $e) {
          continue;
        }
        $item = $item_list->addChild("item");
        if ($object->id === '') {
          continue;
        }
        $item->addAttribute("id", $object->id);
        // Data from search result.
        $item->addChild("title", htmlspecialchars($title));
        $author = !empty($object->record['dc:creator']['oss:sort'][0]) ? $object->record['dc:creator']['oss:sort'][0] : NULL;
        $item->addChild("author", htmlspecialchars($author));
        $item->addChild("type", htmlspecialchars($object->record['dc:type']['dkdcplus:BibDK-Type'][0]));
        $item->addChild("typeIcon");
        $item->addChild("year", isset($object->record['dc:date'][''][0]) ? $object->record['dc:date'][''][0] : '');

        // ToDo provide small images also.
        if (isset($images[$object->localId])) {
          $item->addChild("img", $images[$object->localId]);
          $item->addChild("smallImg", $images[$object->localId]);
        }
      }
    }

    $xml->addChild('blankImage');

    return $xml->asXML();
  }



  private function getClient() {
    if (!isset($this->client)) {
      $this->client = new TingClient(new TingClientRequestAdapter());
    }
    return $this->client;
  }

  private function execute($request) {
    $res = $this->getClient()->execute($request);


    // When the request is for fulltext (doc-book) the result is XML but the
    // next part expect JSON only formatted input. So this hack simply return
    // the XML for now as later on we have to work with open format and XML
    // parsing. So for now simply return the result to fulltext.
    if ($request instanceof TingClientObjectRequest && $request->getOutputType() == 'xml' && $request->getFormat() == 'docbook') {
      return $res;
    }

    return $request->parseResponse($res);
  }

  private function getRequestFactory() {
    if (!isset($this->requestFactory)) {
      $urls = array(
        'search' => TING_SEARCH_URL,
        'scan' => TING_SCAN_URL,
        'object' => TING_SEARCH_URL,
        'collection' => TING_SEARCH_URL,
        'spell' => TING_SPELL_URL,
        'recommendation' => TING_RECOMMENDATION_URL,
        'infomedia' => TING_INFOMEDIA_URL,
    );

      $this->requestFactory = new TingClientRequestFactory($urls);
    }
    return $this->requestFactory;
  }

  public function getObject($faust, $requestKey) {
    if (!empty($faust)) {
      // Build request request and set object id.
      $request = $this->getRequestFactory()->getObjectRequest();
      if (!is_object($request)) {
        return NULL;
      }
      $request->setObjectId($faust);

      $request->setAgency(TING_AGENCY_ID);
      $request->setProfile(TING_SEARCH_PROFILE);

      // Get all relations for the object.

      $request->setAllRelations(TRUE);
      $request->setRelationData('full');


        // Execute the request.
      $object = $this->execute($request);

      $xml = new \SimpleXmlElement('<?xml version=\'1.0\'?><easyOpac></easyOpac>');
      $xml->addAttribute('requestKey', $requestKey);

      if (is_object($object)) {
        $item = $xml->addChild("item");
        $item->addAttribute("id", $object->id);

        $item->addChild("title", htmlspecialchars($object->record['dc:title'][''][0]));

        $author = !empty($object->record['dc:creator']['oss:sort'][0]) ? $object->record['dc:creator']['oss:sort'][0] : NULL;
        $item->addChild("author", htmlspecialchars($author));

        $subj = array();
        if (isset($object->record['dc:subject'])) {
          foreach ($object->record['dc:subject'] as $v) {
            $subj = array_merge($subj, $v);
          }
        }
        if (!empty($subj)) {
          $subjects = $item->addChild('subjects');
          foreach($subj as $v) {
            $subjects->addChild('subject', htmlspecialchars($v));
          }
        }
        $item->addChild("type", $object->record['dc:type']['dkdcplus:BibDK-Type'][0]);
        $item->addChild("physicalDescription", htmlspecialchars(isset($object->record['dcterms:abstract'][''][0]) ? $object->record['dcterms:abstract'][''][0] : ''));

        $item->addChild("year", isset($object->record['dc:date'][''][0]) ? $object->record['dc:date'][''][0] : '');
        $details = $item->addChild('details');
        $details->addChild('language', isset($object->record['dc:language'][''][0]) ? $object->record['dc:language'][''][0] : '');
        $details->addChild("publisher", htmlspecialchars(isset($object->record['dc:publisher'][''][0]) ? $object->record['dc:publisher'][''][0] : ''));
        $details->addChild("version", htmlspecialchars(isset($object->record['dkdcplus:version'][''][0]) ? $object->record['dkdcplus:version'][''][0] : ''));
        $details->addChild("audience", htmlspecialchars(isset($object->record['dcterms:audience'][''][0]) ? $object->record['dcterms:audience'][''][0] : ''));
        $details->addChild("format", htmlspecialchars(isset($object->record['dc:format'][''][0]) ? $object->record['dc:format'][''][0] : ''));
        $details->addChild("pages", htmlspecialchars(isset($object->record['dcterms:extent'][''][0]) ? $object->record['dcterms:extent'][''][0] : ''));

        $item->addChild("notes");
        $item->addChild("issue");
        $item->addChild("price");

        $images = new CoverImageController();
        $images = $images->getCoverImage(array($object->localId));
        if (isset($images[$object->localId])) {
          $item->addChild("img", htmlspecialchars($images[$object->localId]));
        }

        $hardcoddedIds = array('24223795', '25855485', '23753804', '50650898', '26923530');
        $faust = explode(':', $object->id);
        //fetch external resources.
        $relations = $item->addChild('externalResources');
        if (isset($object->relations)) {
          $this->fetchRelations($object->relations, $relations);
        }

        if (in_array($faust[1], $hardcoddedIds)) {
          $this->addHardcoddedRelation($faust[1], $relations);
        }
      }

      return $xml->asXML();
    }

    return NULL;

  }
  private function fetchRelations($relations, &$xmlObj) {
    foreach($relations as $relation) {
      $rel = $xmlObj->addChild('resource');
      $rel->addAttribute('type', htmlspecialchars($relation->getRelationType()));
      $rel->addChild('title', htmlspecialchars($relation->getTitle()));
      $rel->addChild('author', htmlspecialchars($relation->getCreator()));
      $rel->addChild('abstract', htmlspecialchars($relation->getAbstract()));
      $rel->addChild('audience', htmlspecialchars($relation->getAudience()));
      $rel->addChild('year', htmlspecialchars($relation->getDate()));
      $rel->addChild('isPartOf', htmlspecialchars($relation->getPartOf()));
      $rel->addChild('description', htmlspecialchars($relation->getDescription()));
    }
  }

  private function addHardcoddedRelation($faust, &$xmlObj) {
    $file = fopen($faust, "r");
    $a = simplexml_load_string(fread($file, filesize($faust)));
    fclose($file);
    $rel = $xmlObj->addChild('resource');
    $rel->addAttribute('type', 'literatursiden');
    $rel->addChild('title', htmlspecialchars($a->title));
    $rel->addChild('abstract', htmlspecialchars($a->abstract));
    $rel->addChild('description', htmlspecialchars($a->description));
  }

  public function getDepartments() {
    $xml = new \SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?><departments></departments>');

    $facet = 'facet.department';
    $options = array(
      'facets' => array($facet),
      'numFacets' => 9999,
      'reply_only' => TRUE,
      'sort' => 'random',
    );
    $this->doSearch('*', 0, 0, $options);

    foreach ($this->searchResult->facets[$facet]->terms as $term => $count) {
      $dep = $xml->addChild('department');
      $dep->addChild('id', $term);
      $dep->addChild('name', $term);
    }

    return $xml->asXML();
  }
}
