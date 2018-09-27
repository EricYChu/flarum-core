<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\Command\StartPhoneVerification;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Illuminate\Contracts\Validation\Factory;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Validation\Validator;
use ReCaptcha\ReCaptcha;
use Symfony\Component\Translation\TranslatorInterface;

class StartRegisterVerificationController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\CurrentUserSerializer';

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Dispatcher $bus
     * @param Factory $validatorFactory
     */
    public function __construct(Dispatcher $bus, Factory $validatorFactory, SettingsRepositoryInterface $settings, TranslatorInterface $translator)
    {
        $this->bus = $bus;
        $this->validatorFactory = $validatorFactory;
        $this->settings = $settings;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $phone = array_get($request->getParsedBody(), 'data.attributes.phone', '');
        $recaptchaResponse = array_get($request->getParsedBody(), 'data.attributes.recaptchaResponse', '');

        $validation = $this->validatorFactory->make(['phone' => $phone, 'recaptchaResponse' => $recaptchaResponse], ['phone' => 'required|unique:users,phone', 'recaptchaResponse' => 'required']);
        $validation->after(function (Validator $validator) use ($recaptchaResponse) {
            if ($validator->errors()->isEmpty()) {
                $recaptcha = new ReCaptcha($this->settings->get('google_recaptcha_secret_key'));
                if (! $recaptcha->verify($recaptchaResponse)->isSuccess()) {
                    $validator->errors()->add('recaptchaResponse', $this->translator->trans('core.api.invalid_recaptcha_response_message'));
                }
            }
        });
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return $this->bus->dispatch(
            new StartPhoneVerification($request->getAttribute('actor'), array_get($request->getParsedBody(), 'data', []))
        );
    }
}
