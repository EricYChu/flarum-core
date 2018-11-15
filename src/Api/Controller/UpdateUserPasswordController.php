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

use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\User;
use Flarum\Event\UserPasswordWasChanged;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Symfony\Component\Translation\TranslatorInterface;

class UpdateUserPasswordController extends AbstractResourceController
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
     * @var Bus
     */
    protected $bus;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @param Bus $bus
     * @param TranslatorInterface $translator
     * @param CenterService $center
     */
    public function __construct(Bus $bus, TranslatorInterface $translator, CenterService $center)
    {
        $this->bus = $bus;
        $this->translator = $translator;
        $this->center = $center;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return User|mixed
     * @throws PermissionDeniedException
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = array_get($request->getQueryParams(), 'id');
        /** @var User $actor */
        $actor = $request->getAttribute('actor');
        $data = $request->getParsedBody();

        if ($actor->id != $id) {
            throw new PermissionDeniedException;
        }

        $token = $actor->getToken()->center_token;
        $password = array_get($data, 'data.attributes.password');
        $currentPassword = array_get($data, 'meta.password');

        $this->center->updateUserPassword($token, $actor->id, (string)$password, (string)$currentPassword);

        static::$events->fire(new UserPasswordWasChanged($actor));

        return $actor;
    }
}
