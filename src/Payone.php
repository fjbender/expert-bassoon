<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21.03.17
 * Time: 10:23
 */

namespace Payone\AjaxGateway;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Credentials.php';

/**
 * Class Payone
 */
class Payone {

    /**
     * The URL of the Payone API
     */
    const PAYONE_SERVER_API_URL = 'https://api.pay1.de/post-gateway/';
    //const PAYONE_SERVER_API_URL = 'https://int-api.pay1.de/post-gateway/';

    /**
     * performing the curl POST request to the PAYONE platform
     *
     * @param array $request
     * @throws \Exception
     * @return array
     */
    public static function doCurl($request)
    {
        $credentials = new Credentials();
        $client = new \GuzzleHttp\Client(['verify' => false ]);

        if (($response = $client->request('POST', self::PAYONE_SERVER_API_URL, ['form_params' => array_merge($request, $credentials->getCredentials())])) instanceof \Psr\Http\Message\ResponseInterface) {
            $return = self::parseResponse($response);
        } else {
            throw new \Exception('Something went wrong during the HTTP request.');
        }

        return $return;
    }

    /**
     * gets response string an puts it into an array
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @throws \Exception
     * @return array
     */
    public static function parseResponse(\Psr\Http\Message\ResponseInterface $response)
    {
        $responseArray = array();
        $explode = explode(PHP_EOL, $response->getBody());
        foreach ($explode as $e) {
            $keyValue = explode("=", $e);
            if (trim($keyValue[0]) != "") {
                if (count($keyValue) == 2) {
                    $responseArray[$keyValue[0]] = trim($keyValue[1]);
                } else {
                    $key = $keyValue[0];
                    unset($keyValue[0]);
                    $value = implode("=", $keyValue);
                    $responseArray[$key] = $value;
                }
            }
        }
        if ($responseArray['status'] == "ERROR") {
            throw new \Exception(json_encode($responseArray));
        }
        return $responseArray;
    }
}