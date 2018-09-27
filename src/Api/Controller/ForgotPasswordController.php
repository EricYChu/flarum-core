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

use Flarum\Core\Command\RequestPasswordReset;
use Flarum\Core\Repository\UserRepository;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationException;
use ReCaptcha\ReCaptcha;
use Symfony\Component\Translation\TranslatorInterface;
use Illuminate\Contracts\Validation\Factory;

class ForgotPasswordController implements ControllerInterface
{
    /**
     * @var \Flarum\Core\Repository\UserRepository
     */
    protected $users;

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
     * @param \Flarum\Core\Repository\UserRepository $users
     * @param Dispatcher $bus
     */
    public function __construct(UserRepository $users, Factory $validatorFactory, Dispatcher $bus, SettingsRepositoryInterface $settings, TranslatorInterface $translator)
    {
        $this->users = $users;
        $this->bus = $bus;
        $this->validatorFactory = $validatorFactory;
        $this->settings = $settings;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $recaptchaResponse = array_get($request->getParsedBody(), 'recaptchaResponse', '');
        $validation = $this->validatorFactory->make(['recaptchaResponse' => $recaptchaResponse], ['recaptchaResponse' => 'required']);
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

        $phone = array_get($request->getParsedBody(), 'phone');
        $this->bus->dispatch(
            new RequestPasswordReset($phone)
        );

        return new EmptyResponse;
    }
}
