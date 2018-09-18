<?php

namespace Flarum\Core\PhoneVerification;

use Authy\AuthyResponse;

class AuthyApi extends \Authy\AuthyApi
{
    /**
     * @param string $phone_number User's phone_number stored in your database
     * @param string $country_code User's phone country code stored in your database
     *
     * @return AuthyResponse the server response
     */
    public function phoneVerificationStatus($phone_number, $country_code)
    {
        $resp = $this->rest->get('phones/verification/status', array_merge(
            $this->default_options,
            array(
                'query' => array(
                    'phone_number' => $phone_number,
                    'country_code' => $country_code,
                )
            )
        ));

        return new AuthyResponse($resp);
    }
}