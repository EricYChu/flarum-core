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

use Flarum\Core\Command\EditUser;
use Flarum\Core\Exception\PermissionDeniedException;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationException;
use ReCaptcha\ReCaptcha;
use Symfony\Component\Translation\TranslatorInterface;
use Illuminate\Contracts\Validation\Factory;

class UpdateUserController extends AbstractResourceController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\CurrentUserSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

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
        $id = array_get($request->getQueryParams(), 'id');
        $actor = $request->getAttribute('actor');
        $data = array_get($request->getParsedBody(), 'data', []);

        // Require the user's current password if they are attempting to change
        // their own email address, phone number or password.
        if ((isset($data['attributes']['password']) or isset($data['attributes']['email']) or isset($data['attributes']['phone'])) && $actor->id == $id) {
            $password = array_get($request->getParsedBody(), 'meta.password');

            if (! $actor->checkPassword($password)) {
                throw new PermissionDeniedException;
            }
        }

        if (isset($data['attributes']['phone']) && $actor->id == $id) {
            $recaptchaResponse = array_get($data, 'attributes.recaptchaResponse', '');
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
        }

        return $this->bus->dispatch(
            new EditUser($id, $actor, $data)
        );
    }
}
