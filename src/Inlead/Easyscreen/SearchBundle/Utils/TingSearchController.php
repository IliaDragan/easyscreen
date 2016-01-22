<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Utils;

use Inlead\Easyscreen\SearchBundle\TingClient\lib\adapter\TingClientRequestAdapter;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\request\TingClientRequest;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\request\TingClientRequestFactory;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\request\TingClientObjectRequest;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\result\search\TingClientSearchResult;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\TingClient;

class TingSearchController
{
    const TING_SEARCH_URL = 'http://opensearch.addi.dk/4.0.1/';
    const TING_SCAN_URL = 'http://openscan.addi.dk/1.7/';
    const TING_SPELL_URL = 'http://openspell.addi.dk/1.2/';
    const TING_RECOMMENDATION_URL = 'http://openadhl.addi.dk/1.1/';
    const TING_AGENCY_ID = '786000';
    const TING_SEARCH_PROFILE = 'HJOIND';
    const TING_INFOMEDIA_URL = 'http://useraccessinfomedia.addi.dk/1.1/';

    private $requestFactory;

    private $client;

    private $searchResult;

    private $facetbrowser = array(
        0 => array(
            'name' => 'facet.category',
            'title' => 'Børn/voksen',
            'sorting' => 'default',
            'weight' => '-10',
        ),
        1 => array(
            'name' => 'facet.type',
            'title' => 'Materialetype',
            'sorting' => 'default',
            'weight' => '-9',
        ),
        2 => array(
            'name' => 'facet.acSource',
            'title' => 'Kilde',
            'sorting' => 'default',
            'weight' => '-8',
        ),
        3 => array(
            'name' => 'facet.creator',
            'title' => 'Forfatter',
            'sorting' => 'default',
            'weight' => '-7',
        ),
        4 => array(
            'name' => 'facet.language',
            'title' => 'Sprog',
            'sorting' => 'default',
            'weight' => '-6',
        ),
        5 => array(
            'name' => 'facet.subject',
            'title' => 'Emne',
            'sorting' => 'default',
            'weight' => '-5',
        ),
        6 => array(
            'name' => 'facet.date',
            'title' => 'Årstal',
            'sorting' => 'numeric_reverse',
            'weight' => '-4',
        ),
    );

    public function getSearchResult($query, $offset, $results_per_page = 10, array $options = array(), $requestKey = '', $branch = '')
    {
        $request = $this->getRequestFactory()->getSearchRequest();

        if (!is_object($request)) {
            return null;
        }
        $request->setQuery($query);
        $request->setAgency(self::TING_AGENCY_ID);

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

        $request->setAllObjects(isset($options['allObjects']) ? $options['allObjects'] : false);

        // Set search profile
        $request->setProfile(self::TING_SEARCH_PROFILE);

        $request->setRank('rank_general');

        $this->searchResult = $this->execute($request);

        $this->branchFilter($branch);

        $xml = new \SimpleXmlElement('<easyOpac />');

        $xml->addAttribute('requestKey', $requestKey);

        // Create types
        $item_types = $xml->addChild('itemTypes');
        $result_types = array();

        if (is_object($this->searchResult)) {
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

        // Create filters (facets).
        $item_filters = $xml->addChild('filters');

        if (is_object($this->searchResult->facets)) {
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

                if (empty($object->record['dc:title'][''][0]) || empty($object->id)) {
                    continue;
                }

                $item = $item_list->addChild('item');

                $item->addAttribute('id', $object->id);
                // Data from search result.
                $item->addChild('title', htmlspecialchars($object->record['dc:title'][''][0]));
                $item->addChild('author', !empty($object->record['dc:creator']['oss:sort'][0]) ? htmlspecialchars($object->record['dc:creator']['oss:sort'][0]) : '');
                $item->addChild('type', !empty($object->record['dc:type']['dkdcplus:BibDK-Type'][0]) ? htmlspecialchars($object->record['dc:type']['dkdcplus:BibDK-Type'][0]) : '');
                $item->addChild('typeIcon', '');
                $item->addChild('year', !empty($object->record['dc:date'][''][0]) ? $object->record['dc:date'][''][0] : '');

                // ToDo provide small images also.
                if (isset($images[$object->localId])) {
                    $item->addChild('img', !empty($images[$object->localId]['detailUrl']) ? $images[$object->localId]['detailUrl'] : '');
                    $item->addChild('smallImg', !empty($images[$object->localId]['thumbnailUrl']) ? $images[$object->localId]['thumbnailUrl'] : '');
                }
            }
        }

        $xml->addChild('blankImage');

        return $xml->asXML();
    }

    private function getClient()
    {
        if (!isset($this->client)) {
            $this->client = new TingClient(new TingClientRequestAdapter());
        }

        return $this->client;
    }

    private function execute(TingClientRequest $request)
    {
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

    private function getRequestFactory()
    {
        if (!isset($this->requestFactory)) {
            $urls = array(
                'search' => self::TING_SEARCH_URL,
                'scan' => self::TING_SCAN_URL,
                'object' => self::TING_SEARCH_URL,
                'collection' => self::TING_SEARCH_URL,
                'spell' => self::TING_SPELL_URL,
                'recommendation' => self::TING_RECOMMENDATION_URL,
                'infomedia' => self::TING_INFOMEDIA_URL,
            );

            $this->requestFactory = new TingClientRequestFactory($urls);
        }

        return $this->requestFactory;
    }

    public function getObject($faust, $requestKey)
    {
        // Build request request and set object id.
        $request = $this->getRequestFactory()->getObjectRequest();
        $request->setObjectId($faust);
        $request->setAgency(self::TING_AGENCY_ID);
        $request->setProfile(self::TING_SEARCH_PROFILE);

        // Set complete relations for the object.
        $request->setAllRelations(true);
        $request->setRelationData('full');

        // Execute the request.
        $object = $this->execute($request);

        if (!is_object($object)) {
            throw new \Exception('No response received. Check the faust number.');
        }

        $xml = new \SimpleXmlElement('<easyOpac />');
        $xml->addAttribute('requestKey', $requestKey);

        $item = $xml->addChild('item');
        $item->addAttribute('id', $object->id);

        $item->addChild('title', htmlspecialchars($object->record['dc:title'][''][0]));
        $author = !empty($object->record['dc:creator']['oss:sort'][0]) ? $object->record['dc:creator']['oss:sort'][0] : '';
        $item->addChild('author', htmlspecialchars($author));

        if (isset($object->record['dc:subject'])) {
            $subjects = $item->addChild('subjects');
            foreach ($object->record['dc:subject'] as $dkdcplus) {
                foreach ($dkdcplus as $v) {
                    $subjects->addChild('subject', htmlspecialchars($v));
                }
            }
        }

        $item->addChild('type', $object->record['dc:type']['dkdcplus:BibDK-Type'][0]);
        $item->addChild('physicalDescription', !empty($object->record['dcterms:abstract'][''][0]) ? htmlspecialchars($object->record['dcterms:abstract'][''][0]) : '');
        $item->addChild('year', !empty($object->record['dc:date'][''][0]) ? $object->record['dc:date'][''][0] : '');

        $details = $item->addChild('details');
        $details->addChild('language', !empty($object->record['dc:language'][''][0]) ? $object->record['dc:language'][''][0] : '');
        $details->addChild('publisher', !empty($object->record['dc:publisher'][''][0]) ? htmlspecialchars($object->record['dc:publisher'][''][0]) : '');
        $details->addChild('version', !empty($object->record['dkdcplus:version'][''][0]) ? htmlspecialchars($object->record['dkdcplus:version'][''][0]) : '');
        $details->addChild('audience', !empty($object->record['dcterms:audience'][''][0]) ? htmlspecialchars($object->record['dcterms:audience'][''][0]) : '');
        $details->addChild('format', !empty($object->record['dc:format'][''][0]) ? htmlspecialchars($object->record['dc:format'][''][0]) : '');
        $details->addChild('pages', !empty($object->record['dcterms:extent'][''][0]) ? htmlspecialchars($object->record['dcterms:extent'][''][0]) : '');

        $item->addChild('notes');
        $item->addChild('issue');
        $item->addChild('price');

        $images = new CoverImageController();
        $images = $images->getCoverImage(array(
            $object->localId,
        ));

        if (!empty($images[$object->localId])) {
            $item->addChild('img', !empty($images[$object->localId]['detailUrl']) ? $images[$object->localId]['detailUrl'] : '');
            $item->addChild('thumb', !empty($images[$object->localId]['thumbnailUrl']) ? $images[$object->localId]['thumbnailUrl'] : '');
        }

        // Fetch external resources.
        $relations = $item->addChild('externalResources');
        if (isset($object->relations) && is_array($object->relations)) {
            foreach ($object->relations as $relation) {
                $rel = $relations->addChild('resource');
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

        return str_replace('\'', '\\\'', $xml->asXML());
    }

    public function getDepartments()
    {
        $xml = new \SimpleXmlElement('<departments />');

        $facet = 'facet.department';
        $options = array(
            'facets' => array(
                $facet,
            ),
            'numFacets' => 9999,
            'reply_only' => true,
            'sort' => 'random',
        );
        $this->getSearchResult('*', 0, 0, $options);

        foreach ($this->searchResult->facets[$facet]->terms as $term => $count) {
            $dep = $xml->addChild('department');
            $dep->addChild('id', $term);
            $dep->addChild('name', $term);
        }

        return str_replace('\'', '\\\'', $xml->asXML());
    }

    private function branchFilter($branch)
    {
        if (empty($branch) || !is_object($this->searchResult) || !($this->searchResult instanceof TingClientSearchResult) || $this->searchResult->numTotalCollections == 0) {
            return;
        }

        // Accumulate object id's first.
        $object_ids = array();
        foreach ($this->searchResult->collections as $collection) {
            $object = reset($collection->objects);
            $object_ids[] = $object->localId;
        }

        // Fetch holdings.
        $provider = new AlmaProvider();
        $details = $provider->getRecordDetail($object_ids);

        // Pick items that exist in the specified branch.
        $matches = array();

        foreach ($details['records'] as $item_id => $detail) {
            // Periodical items.
            if (isset($detail['issues'])) {
                foreach ($detail['issues'] as $year => $holdings) {
                    foreach ($holdings as $holding) {
                        if (in_array($branch, $holding['branches'])) {
                            $matches[] = $item_id;
                        }
                    }
                }
            }
            // Normal items.
            elseif (isset($detail['holdings'])) {
                foreach ($detail['holdings'] as $holding) {
                    if ($holding['branch_id'] == $branch) {
                        $matches[] = $item_id;
                    }
                }
            }
        }

        // Wipe out items that do not meet the branch filter.
        $obsolete = array_diff($object_ids, $matches);
        foreach ($this->searchResult->collections as $k => $collection) {
            $object = reset($collection->objects);
            if (in_array($object->localId, $obsolete)) {
                unset($this->searchResult->collections[$k]);
                $this->searchResult->numTotalCollections--;
            }
        }
    }
}
