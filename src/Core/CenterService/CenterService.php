<?php

namespace Flarum\Core\CenterService;

use Acgn\Center\Client;
use Acgn\Center\Exceptions\InvalidParamsResponseException;
use Acgn\Center\Notification;
use Acgn\Center\Resources;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Str;
use Symfony\Component\Translation\TranslatorInterface;

class CenterService
{
    use DispatchEventsTrait;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Notification
     */
    protected $notification;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @param TranslatorInterface $translator
     * @param Application $app
     * @param Factory $validator
     */
    public function __construct(TranslatorInterface $translator, Application $app, Factory $validator)
    {
        $this->app = $app;
        $config = $app->config('center');
        $this->client = new Client(
            $config['app_key'],
            $config['app_secret'],
            $config['endpoint'],
            $translator->getLocale(),
            null
        );
        $this->notification = new Notification(
            $config['topic_owner'],
            $config['subscription_name'],
            $config['debug']
        );
        $this->validator = $validator;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function setAccessToken(string $token)
    {
        $this->client->setAccessToken($token);
        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function addUserCreationListener(\Closure $callback)
    {
        $this->notification->addUserCreationListener($callback);
        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function addUserUpdatingListener(\Closure $callback)
    {
        $this->notification->addUserUpdatingListener($callback);
        return $this;
    }

    /**
     * @return void
     */
    public function listen(): void
    {
        $this->notification->listen();
    }

    /**
     * @return Resources
     */
    public function resources(): Resources
    {
        return $this->client->resources();
    }

    /**
     * @param string $verificationToken
     * @param string $username
     * @param string $password
     * @param string $email
     * @return \Acgn\Center\Models\User
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function signUp(string $verificationToken, string $username, string $password, string $email)
    {
        try {
            $res = $this->resources()->users()->create($verificationToken, $username, $password, $email);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    /**
     * @param string $identification
     * @param string $password
     * @return \Acgn\Center\Models\Auth
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function signIn(string $identification, string $password)
    {
        try {
            $res = $this->resources()->auth()->create($identification, $password);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    public function signOut(string $token)
    {
        try {
            $this->setAccessToken($token)->resources()->auth()->delete();
        } catch (\Throwable $e) {}
    }

    /**
     * @param string $token
     * @param int $id
     * @param null|string $username
     * @param null|string $email
     * @return \Acgn\Center\Models\User
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function updateUser(string $token, int $id, ?string $username = null, ?string $email = null)
    {
        try {
            $res = $this->setAccessToken($token)
                ->resources()->users($id)->update($username, $email);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    /**
     * @param string $token
     * @param int $id
     * @param string $verificationToken
     * @return \Acgn\Center\Models\User
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function updateUserPhone(string $token, int $id, string $verificationToken)
    {
        try {
            $res = $this->setAccessToken($token)
                ->resources()->users($id)->phone()->update($verificationToken);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    /**
     * @param string $token
     * @param int $id
     * @param string $email
     * @param string $currentPassword
     * @return \Acgn\Center\Models\User
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function updateUserEmail(string $token, int $id, string $email, string $currentPassword)
    {
        try {
            $res = $this->setAccessToken($token)
                ->resources()->users($id)->email()->update($email, $currentPassword);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    /**
     * @param string $verificationToken
     * @param string $password
     * @return null
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function resetUserPassword(string $verificationToken, string $password)
    {
        try {
            $res = $this->resources()->forgot()->create($verificationToken, $password);
        } catch (InvalidParamsResponseException $e) {
            throw $this->handleInvalidParamsResponseException($e);
        }

        return $res;
    }

    protected function handleInvalidParamsResponseException(InvalidParamsResponseException $exception)
    {
        $errors = $exception->getErrors();
        $validator = $this->validator->make([], []);
        foreach ($errors as $key => $descriptions) {
            $key = Str::camel($key);
            foreach ($descriptions as $description) {
                $validator->errors()->add($key, $description);
            }
        }
        return new ValidationException($validator);
    }
}