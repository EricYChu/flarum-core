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
     * @param Dispatcher $bus
     * @param Factory $validatorFactory
     */
    public function __construct(Dispatcher $bus, Factory $validatorFactory)
    {
        $this->bus = $bus;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $phone = array_get($request->getParsedBody(), 'data.attributes.phone', '');

        $validation = $this->validatorFactory->make(['phone' => $phone], ['phone' => 'required|unique:users,phone']);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return $this->bus->dispatch(
            new StartPhoneVerification($request->getAttribute('actor'), array_get($request->getParsedBody(), 'data', []))
        );
    }
}
