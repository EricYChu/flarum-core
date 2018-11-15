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

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Core\Command\EditUser;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Illuminate\Contracts\Validation\Factory;

class UpdateUserController extends AbstractResourceController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

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
     * @param Factory $validatorFactory
     * @param SettingsRepositoryInterface $settings
     * @param TranslatorInterface $translator
     */
    public function __construct(Dispatcher $bus, Factory $validatorFactory, SettingsRepositoryInterface $settings, TranslatorInterface $translator)
    {
        $this->bus = $bus;
        $this->validatorFactory = $validatorFactory;
        $this->settings = $settings;
        $this->translator = $translator;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = array_get($request->getQueryParams(), 'id');
        $actor = $request->getAttribute('actor');
        $data = array_get($request->getParsedBody(), 'data', []);

        if ($actor->id == $id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        return $this->bus->dispatch(
            new EditUser($id, $actor, $data)
        );
    }
}
