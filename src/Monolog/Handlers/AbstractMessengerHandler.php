<?php

namespace PrivateDev\Monolog\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use PrivateDev\Monolog\Exceptions\MessengerHandlerException;

/**
 * Allows you to send log messages via curl.
 *
 * Class AbstractMessengerHandler
 *
 * @package App\Components\Monolog\Handler
 */
abstract class AbstractMessengerHandler extends AbstractProcessingHandler
{
    protected $successStatusCodes = [200, 201, 202, 203, 204, 205, 206, 207];

    /**
     * @param array $content
     */
    protected function write(array $content)
    {
        $ch = $this->makeCh($content);

        $response = curl_exec($ch);
        $this->checkResult($ch, $response);
        curl_close($ch);
    }

    /**
     * Prepare data for curl_exec
     *
     * @return resource
     */
    protected function makeCh($content)
    {
        $url = $this->getUrl();
        $data = json_encode($this->makeRequestData($content));

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        return $ch;
    }

    /**
     * Check sendMessage response
     *
     * @param resource          $ch
     * @param array|string|null $response
     * @throws MessengerHandlerException
     */
    private function checkResult($ch, $response)
    {
        $info = curl_getinfo($ch);
        $code = (int) $info['http_code'];

        if (! in_array($code, $this->successStatusCodes)) {
            curl_close($ch);
            throw new MessengerHandlerException(json_encode($response), $code);
        }
    }

    /**
     * Url to send message. Application Webhook for example
     *
     * @return string Valid url
     */
    abstract protected function getUrl();

    /**
     * Modify data for Request
     *
     * @param $content
     * @return mixed
     */
    abstract protected function makeRequestData($content);
}
