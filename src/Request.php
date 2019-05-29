<?php
/******************************************************************************
 * Copyright 2017 Okta, Inc.                                                  *
 *                                                                            *
 * Licensed under the Apache License, Version 2.0 (the "License");            *
 * you may not use this file except in compliance with the License.           *
 * You may obtain a copy of the License at                                    *
 *                                                                            *
 *      http://www.apache.org/licenses/LICENSE-2.0                            *
 *                                                                            *
 * Unless required by applicable law or agreed to in writing, software        *
 * distributed under the License is distributed on an "AS IS" BASIS,          *
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.   *
 * See the License for the specific language governing permissions and        *
 * limitations under the License.                                             *
 ******************************************************************************/

namespace Okta\JwtVerifier;

use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Request
{
    protected $httpClient;
    protected $uriFactory;
    protected $messageFactory;
    protected $streamFactory;
    protected $cacheItemPool;

    /**
     * The UriInterface of the request to be made.
     *
     * @var UriInterface
     */
    protected $url;

    /**
     * The set of query parameters to send with request.
     *
     * @var array
     */
    protected $query = [];

    public function __construct(
        HttpClient $httpClient = null,
        UriFactory $uriFactory = null,
        MessageFactory $messageFactory = null,
        StreamFactory $streamFactory = null,
        CacheItemPoolInterface $cacheItemPool = null
    ) {
        $plugins = [];
        if ($cacheItemPool) {
            $streamFactory = $streamFactory ?: StreamFactoryDiscovery::find();
            $plugins[] = new CachePlugin($cacheItemPool, $streamFactory);
        }
        $this->uriFactory = $uriFactory ?: UriFactoryDiscovery::find();
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
        $this->httpClient = new PluginClient(
            $httpClient ?: HttpClientDiscovery::find(),
            $plugins
        );
    }

    public function setUrl($url): Request
    {
        $this->url = $this->uriFactory->createUri($url);
        return $this;
    }

    public function withQuery($key, $value = null): Request
    {
        $this->query[$key] = $value;

        return $this;
    }

    public function get(): ResponseInterface
    {
        return $this->request('GET');
    }

    protected function request($method): ResponseInterface
    {
        $headers = [];
        $headers['Accept'] = 'application/json';

        if (!empty($this->query)) {
            $this->url = $this->url->withQuery(http_build_query($this->query));
        }

        $request = $this->messageFactory->createRequest($method, $this->url, $headers);

        return $this->httpClient->sendRequest($request);

    }


}