<?php

namespace Flarum\Core\PhoneVerification\PaaSoo;

use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Flarum\Core\PhoneToken;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\ParseResponseException;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\HttpResponseException;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\SendMessageException;
use Flarum\Core\PhoneVerification\PhoneVerificationInterface;
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

class PhoneVerification implements PhoneVerificationInterface
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
     * @var PaaSooApi
     */
    protected $paasoo;

    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, PhoneVerificationValidator $validator, TranslatorInterface $translator)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->paasoo = new PaaSooApi($this->settings->get('sms_paasoo_api_key', ''), $this->settings->get('sms_paasoo_api_secret', ''), $this->settings->get('sms_paasoo_sender_number', ''));
    }

    /**
     * @param User $actor
     * @param string $phone
     * @return void
     * @throws ValidationException
     */
    public function start(User $actor, $phone): void
    {
        $phone = trim($phone, '+');

        $data = ['phone' => $phone];

        $validator = $this->validator->makeValidator($data);

        $results = [];

        $validator->after(function (Validator $validator) use ($phone, &$results) {
            if ($validator->errors()->isEmpty()) {
                [$countryCode, $phoneNumber] = CallingCode::parsePhone($phone);

                if (empty($countryCode) or empty($phoneNumber)) {
                    $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                } else {
                    if (!$this->paasoo->validate($phone)) {
                        $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                    } else {
                        try {
                            $token = PhoneToken::generate($phone);
                            $senderName = $this->settings->get('sms_sender_name');
                            $code = $token->code;
                            $minutes = 5;
                            if ($countryCode == '86') {
                                $message =  str_replace(
                                    ['{sender}', '{code}', '{minutes}'],
                                    [$senderName, $code, $minutes],
                                    $this->settings->get('sms_paasoo_cn_message_template')
                                );
                            } else {
                                $message = $this->translator->trans('core.api.invalid_verification_code_message', [
                                    'sender' => $senderName,
                                    'code' => $code,
                                    'minutes' => $minutes,
                                ]);
                            }

                            $this->paasoo->send($phone, $message);

                            $results = [
                                'expires' => $token->expires(),
                            ];
                        } catch (ParseResponseException | HttpResponseException $e) {
                            $validator->errors()->add('phone', $this->translator->trans('core.api.failed_send_verification_code_message'));
                        } catch (SendMessageException | Exception $e) {
                            $validator->errors()->add('phone', $e->getMessage());
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->events->fire(
            new PhoneVerificationWasStarted($actor, $data, [])
        );
    }

    /**
     * @param User $actor
     * @param string $phone
     * @param string $verificationCode
     * @return void
     * @throws ValidationException
     */
    public function check(User $actor, $phone, $verificationCode): void
    {
        $phone = trim($phone, '+');

        $data = ['phone' => $phone, 'verificationCode' => $verificationCode];

        $validator = $this->validator->makeValidator($data);

        $validator->after(function (Validator $validator) use ($phone, $verificationCode) {
            if (empty($countryCode) or empty($phoneNumber)) {
                [$countryCode, $phoneNumber] = CallingCode::parsePhone($phone);
                if (empty($countryCode) or empty($phoneNumber)) {
                    $validator->errors()->add('phone', $this->translator->trans('core.api.invalid_phone_message'));
                } else {
                    try {
                        $token = PhoneToken::validOrFail($phone, $verificationCode);
                        $token->delete();
                    } catch (InvalidConfirmationTokenException $e) {
                        $validator->errors()->add('verificationCode', $this->translator->trans('core.api.invalid_verification_code_message'));
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
}