<?php

namespace Vnn\WpApiClient\Endpoint;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Vnn\WpApiClient\WpClient;

/**
 * Class AbstractWpEndpoint
 * @package Vnn\WpApiClient\Endpoint
 */
abstract class AbstractWpEndpoint
{
    /**
     * @var WpClient
     */
    protected $client;

    /**
     * Users constructor.
     * @param WpClient $client
     */
    public function __construct(WpClient $client)
    {
        $this->client = $client;
    }

    abstract protected function getEndpoint();

    /**
     * @param int $id
     * @param array $params - parameters that can be passed to GET
     *        e.g. for tags: https://developer.wordpress.org/rest-api/reference/tags/#arguments
     * @return array
     * @throws \RuntimeException
     */
    public function get($id = null, array $params = null)
    {
        $uri = $this->getEndpoint();
        $uri .= (is_null($id)?'': '/' . $id);
        $uri .= (is_null($params)?'': '?' . http_build_query($params));

        $request = new Request('GET', $uri);
        $response = $this->client->send($request);

        if ($response->hasHeader('Content-Type')
            && substr($response->getHeader('Content-Type')[0], 0, 16) === 'application/json') {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new RuntimeException('Unexpected response');
    }

    /**
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    public function save(array $data)
    {
        $url = $this->getEndpoint();

        if (isset($data['id'])) {
            $url .= '/' . $data['id'];
            unset($data['id']);
        }

        $request = new Request('POST', $url, ['Content-Type' => 'application/json'], json_encode($data));
        $response = $this->client->send($request);

        if ($response->hasHeader('Content-Type')
            && substr($response->getHeader('Content-Type')[0], 0, 16) === 'application/json') {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new RuntimeException('Unexpected response');
    }
    
    public function delete(int $id, array $params = null) : void
    {
        $uri = $this->getEndpoint();
        $uri .= '/'.$id;
        $uri .= (is_null($params) ? '' : '?'.http_build_query($params));

        $request = new Request('DELETE', $uri);
        $response = $this->client->send($request);

        if (isset($params['force']) && $params['force'] && !$this->getResponseKey($response, 'deleted', false)) {
            throw new RuntimeException("Delete not successfull for id {$id} / endpoint: {$this->getEndpoint()}");
        }
    }
    
      /**
     * @return bool|mixed
     */
    private function getResponseKey(
        ResponseInterface $response,
        string $key,
        bool $throwException = true,
        $defaultValue = false
    ) {
        if (!$response->hasHeader('Content-Type')
            || substr($response->getHeader('Content-Type')[0], 0, 16) !== 'application/json') {
            throw new RuntimeException('Unexpected response');
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data[$key])) {
            if ($throwException) {
                throw new RuntimeException("Key {$key} not in response!");
            }

            return $defaultValue;
        }

        return $data[$key];
    }
}
