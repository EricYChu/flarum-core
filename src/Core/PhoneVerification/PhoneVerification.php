<?php

namespace Flarum\Core\PhoneVerification;

use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Core\Validator\PhoneValidator;
use Flarum\Core\Validator\PhoneVerificationValidator;
use Flarum\Event\PhoneVerificationWasChecked;
use Flarum\Event\PhoneVerificationWasStarted;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Util\CallingCode;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
use Exception;
use Closure;

class PhoneVerification
{
    use DispatchEventsTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var PhoneValidator
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var AuthyApi
     */
    protected $authy;

    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, PhoneVerificationValidator $validator, TranslatorInterface $translator)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->authy = new AuthyApi($this->settings->get('sms_twilio_verification_api_key'));
    }

    /**
     * @param User $actor
     * @param string $phone
     * @return array
     * @throws ValidationException
     */
    public function start(User $actor, $phone)
    {
        $data = ['phone' => $phone];

        $validator = $this->validator->makeValidator($data);

        $results = [];

        $validator->after(function (Validator $validator) use ($phone, &$results) {
            if ($validator->errors()->isEmpty()) {
                [$countryCode, $phoneNumber] = CallingCode::parsePhone($phone);

                if (empty($countryCode) or empty($phoneNumber)) {
                    $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                } else {
                    $response = $this->authy->phoneInfo($phoneNumber, $countryCode);
                    if (!$response->ok() or !$response->bodyvar('provider')) {
                        $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                    } else {
                        $response = $this->authy->phoneVerificationStart($phoneNumber, $countryCode, 'sms', 6, $this->translator->getLocale());
                        if ($response->ok()) {
                            $results = [
                                'uuid' => $response->bodyvar('uuid'),
                                'expires' => $response->bodyvar('seconds_to_expire'),
                                'cellphone' => $response->bodyvar('is_cellphone'),
                                'message' => $response->bodyvar('message'),
                                'carrier' => $response->bodyvar('carrier'),
                            ];
                        } else {
                            $validator->errors()->add('phone', $this->translator->trans('core.api.failed_send_verification_code_message'));
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->events->fire(
            new PhoneVerificationWasStarted($actor, $data, $results)
        );

        return $results;
    }

    /**
     * @param User $actor
     * @param string $phone
     * @param string $verificationCode
     * @throws ValidationException
     */
    public function check(User $actor, $phone, $verificationCode)
    {
        $data = ['phone' => $phone, 'verification_code' => $verificationCode];

        $validator = $this->validator->makeValidator($data);

        $validator->after(function (Validator $validator) use ($phone, $verificationCode) {
            if (empty($countryCode) or empty($phoneNumber)) {
                [$countryCode, $phoneNumber] = CallingCode::parsePhone($phone);
                if (empty($countryCode) or empty($phoneNumber)) {
                    $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                } else {
                    $response = $this->authy->phoneVerificationCheck($phoneNumber, $countryCode, $verificationCode);
                    if (!$response->ok()) {
                        $validator->errors()->add('verification_code', $this->translator->trans('core.api.invalid_verification_code_message'));
                    }
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->events->fire(
            new PhoneVerificationWasChecked($actor, $data)
        );
    }

    public function status($phone)
    {
        [$countryCode, $phoneNumber] = CallingCode::parsePhone($phone);
        if ($countryCode and $phoneNumber) {
            $response = $this->authy->phoneVerificationStatus($phoneNumber, $countryCode);
            if ($response->ok() and $response->bodyvar('status') === 'verified') {
                return true;
            }
        }
        return false;
    }
}