<?php

namespace App\Service;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class APICaller
{
    /**
     * @var
     */
    public $params;

    /**
     * APICaller constructor.
     * @param ParameterBagInterface $params
     */
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
    /**
     * @param $url
     * @return mixed
     */
    public function launchCall($url)
    {
        // initialise curl
        $curl = curl_init();
        // set curl options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $this->prepareWsseHeader(),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        // execute curl
        $response = curl_exec($curl);
        // decode json result
        $json = json_decode($response, true);
        return $json;
    }

    /**
     * @return array
     */
    public function prepareWsseHeader()
    {
        // get username from parameters
        $username = $this->params->get('API_USERNAME');
        // get password from parameters
        $password = $this->params->get('API_PASSWORD');
        $nonce = "";                    // The nonce
        $nonce64 = "";                    // The nonce with a Base64 encoding
        $date = "";                    // The date of the request, in  ISO 8601 format
        $digest = "";                    // The password digest needed to authenticate you
        $header = "";                    // The final header to put in your request

        // Making the nonce and the encoded nonce
        $chars = "0123456789abcdhi";
        for ($i = 0; $i < 32; $i++) {
            $nonce .= $chars[rand(0, 15)];
        }
        $nonce64 = base64_encode($nonce);
        // Getting the date at the right format (e.g. YYYY-MM-DDTHH:MM:SSZ)
        $date = gmdate('c');
        $date = substr($date, 0, 19) . "Z";
        // Getting the password digest
        $digest = base64_encode(sha1($nonce . $date . $password, true));
        // Getting the X-WSSE header to put in request
        $header = sprintf('X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"', $username, $digest, $nonce64, $date);
        $wsseHeader[] = $header;
        $wsseHeader[] = 'Content-Type: application/json';
        return $wsseHeader;
    }


}