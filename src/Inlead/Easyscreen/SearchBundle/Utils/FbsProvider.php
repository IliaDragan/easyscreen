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
    const FBS_BASE_URL = "https://et.cicero-fbs.com/rest/external/v1/DK-761500";

    private $password = "password";

    private $username = "external";

    /**
     * @param string $method
     * @param string $path
     * @param array  $options
     * @return mixed
     */
    public function request($method, $path, array $options)
    {
        $client = new Client();

        $permOptions = array(
          'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
          ),
        );

        $options = array_merge($permOptions, $options);

        $req = $client->request(
            $method,
            self::FBS_BASE_URL.$path,
            $options
        );

        $body = $req->getBody()->getContents();
        $response = json_decode($body);

        return $response;
    }

    /**
     * @return mixed
     */
    public function authenticate()
    {
        $req = $this->request(
            'POST',
            '/authentication/login',
            array(
                'body' => '{"password":"'.$this->password.'", "username": "'.$this->username.'"}',
            )
        );

        $response = new Response();
        $response->headers->set('X-Session', $req->{'sessionKey'});
        $response->send();

        return $req;
    }

    /**
     * @param array $items
     * @return array
     */
    public function getAvailability(array $items)
    {
        $availability = array();
        foreach ($items as $item) {
            $key = $this->authenticate();

            $req = $this->request(
                'GET',
                '/catalog/availability',
                array(
                  'headers' => array(
                    'X-Session' => $key->{'sessionKey'},
                  ),
                  'query' => array("recordid" => $item),
                )
            );

            foreach ($req as $v) {
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

        $key = $this->authenticate();

        $req = $this->request(
            'GET',
            '/branches',
            array(
              'headers' => array(
                'X-Session' => $key->{'sessionKey'},
              ),
            )
        );

        foreach ($req as $branch) {
            $branches[$branch->branchId] = $branch->title;
        }

        return $branches;
    }
}
