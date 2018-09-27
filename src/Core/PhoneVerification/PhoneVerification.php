<?php

namespace Flarum\Core\PhoneVerification;

use Flarum\Core\User;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\Validator\PhoneVerificationValidator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @method start(User $actor, string $phone): array;
 * @method check(User $actor, string $phone, string $verificationCode): void;
 */
class PhoneVerification
{
    use DispatchEventsTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var PhoneVerificationInterface
     */
    protected $driver;

    /**
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param PhoneVerificationValidator $validator
     * @param TranslatorInterface $translator
     */
    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, PhoneVerificationValidator $validator, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $class = '\Flarum\Core\PhoneVerification\\' . $this->settings->get('sms_driver') . '\PhoneVerification';
        $this->driver = new $class($events, $settings, $validator, $translator);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->driver, $name], $arguments);
    }
}