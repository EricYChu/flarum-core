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

use Flarum\Core\Command\ConfirmPhone;
use Flarum\Core\Exception\PermissionDeniedException;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Illuminate\Contracts\Validation\Factory;

class ConfirmPhoneController extends AbstractResourceController
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
        $actor = $request->getAttribute('actor');

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $body = $request->getParsedBody();
        $verificationToken = array_get($body, 'data.attributes.verificationToken', '');

        return $this->bus->dispatch(
            new ConfirmPhone($actor, $verificationToken)
        );
    }
}
