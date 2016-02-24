<?php

namespace Inlead\Easyscreen\SearchBundle\Utils;

use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

/**
 * Class FbsProvider
 * @package Inlead\Easyscreen\SearchBundle\Utils
 */
class FbsProvider
{
    /**
     * @return mixed
     */
    public function getInstance()
    {
        $password = "password";
        $username = "external";

        $client = new Client();
        $res = $client->request(
            'POST',
            'https://et.cicero-fbs.com/rest/external/v1/DK-761500/authentication/login',
            array(
              'body' => '{"password":"'.$password.'", "username": "'.$username.'"}',
              'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
              ),
            )
        );

        $body = $res->getBody();
        $stringBody = (string) $body;
        $key = json_decode($stringBody);

        $response = new Response();
        $response->headers->set('X-Session', $key->{'sessionKey'});
        $response->send();

        return $key;
    }

    /**
     * @param array $items
     * @return array
     */
    public function getAvailability(array $items)
    {
        $availability = array();
        foreach ($items as $item) {
            $res = $this->getInstance();

            $client = new Client();
            $res = $client->request(
                "GET",
                "https://et.cicero-fbs.com/rest/external/v1/DK-761500/catalog/availability",
                array(
                  'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'X-Session'    => $res->{'sessionKey'},
                  ),
                  'query'   => ["recordid" => $item],
                )
            );

            $res = $res->getBody()->getContents();

            $res = json_decode($res);
            foreach ($res as $v) {
                $availability[$v->recordId] = array(
                  'available' => $v->available,
                );
            }
        }

        return $availability;
    }

    /**
     * @return array
     */
    public function getReservationBranches()
    {
        $branches = array();

        $res = $this->getInstance();

        $client = new Client();
        $res = $client->request(
            "GET",
            "https://et.cicero-fbs.com/rest/external/v1/DK-761500/branches",
            array(
              'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'X-Session'    => $res->{'sessionKey'},
              ),
            )
        );
        $res = $res->getBody()->getContents();
        $res = json_decode($res);

        foreach ($res as $branch) {
            $branches[$branch->branchId] = $branch->title;
        }

        return $branches;
    }
}
