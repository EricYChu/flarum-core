<?php

namespace Flarum\Core\PhoneVerification\PaaSoo;

use Exception;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\ParseResponseException;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\HttpResponseException;
use Flarum\Core\PhoneVerification\PaaSoo\Exception\SendMessageException;
use Throwable;

class PaaSooApi
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $from;

    /**
     * @param string $key
     * @param string $secret
     * @param string $from
     */
    public function __construct(string $key, string $secret, string $from)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->from = $from;
    }

    /**
     * @param string $path
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function request(string $path, array $params): array
    {
        $path = trim($path, '/');
        $url = "https://api.paasoo.com/{$path}?key={$this->key}&secret={$this->secret}&".http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            try {
                $data = json_decode($data, true);
            } catch (Throwable $e) {
                throw new ParseResponseException;
            }
        } else {
            throw new HttpResponseException;
        }

        return $data;
    }

    /**
     * @param string $phone
     * @return bool
     * @throws Exception
     */
    public function validate(string $phone): bool
    {
        $data = $this->request('lookup', [
            'number' => $phone,
        ]);

        return !empty($data['mccmnc']);
    }

    /**
     * @param string $phone
     * @param string $message
     * @throws Exception
     */
    public function send(string $phone, string $message): void
    {
        $data = $this->request('json', [
            'from' => $this->from,
            'to'   => $phone,
            'text' => $message,
        ]);

        if ($data['status'] !== '0') {
            throw new SendMessageException($data['status_code']);
        }
    }
}