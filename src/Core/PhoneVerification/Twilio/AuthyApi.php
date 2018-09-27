<?php

namespace Flarum\Core\PhoneVerification\Twilio;

use Authy\AuthyResponse;
use GuzzleHttp\Psr7\Response;

class AuthyApi extends \Authy\AuthyApi
{
    /**
     * @param string $phoneNumber User's phone_number stored in your database
     * @param string $countryCode User's phone country code stored in your database
     *
     * @return AuthyResponse the server response
     */
    public function phoneVerificationStatus($phoneNumber, $countryCode)
    {
        $apiKey = $this->rest->getConfig('headers')['X-Authy-API-Key'];
        $url = "https://api.authy.com/protected/json/phones/verification/status?api_key={$apiKey}&phone_number={$phoneNumber}&country_code={$countryCode}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new AuthyResponse(new Response($httpCode, [], $data));
    }
//    public function phoneVerificationStatus($phone_number, $country_code)
//    {
//        $resp = $this->rest->get('phones/verification/status', array_merge(
//            $this->default_options,
//            array(
//                'query' => array(
//                    'phone_number' => $phone_number,
//                    'country_code' => $country_code,
//                )
//            )
//        ));
//
//        return new AuthyResponse($resp);
//    }
}