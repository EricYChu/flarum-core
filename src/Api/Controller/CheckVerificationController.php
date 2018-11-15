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

use Flarum\Core\Command\CheckVerification;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CheckVerificationController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\VerificationTokenSerializer';

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return $this->bus->dispatch(
            new CheckVerification($request->getAttribute('actor'), [
                'attributes' => [
                    'id' => array_get($request->getQueryParams(), 'id'),
                    'verificationCode' => array_get($request->getQueryParams(), 'verificationCode'),
                ]
            ])
        );
    }
}
