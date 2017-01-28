<?php

/*
 * Copyright (C) 2013-2016 Mailgun
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Mailgun\Api;

use Mailgun\Assert;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
trait Pagination
{
    abstract protected function httpGet($path, array $parameters = [], array $requestHeaders = []);

    abstract protected function safeDeserialize(ResponseInterface $response, $className);

    /**
     * @param mixed $response
     *
     * @return mixed|null
     */
    public function getPaginationNext($response)
    {
        return $this->getPaginationUrl($response->getNextUrl(), get_class($response));
    }

    /**
     * @param mixed $response
     *
     * @return mixed|null
     */
    public function getPaginationPrevious($response)
    {
        return $this->getPaginationUrl($response->getPreviousUrl(), get_class($response));
    }

    /**
     * @param mixed $response
     *
     * @return mixed|null
     */
    public function getPaginationFirst($response)
    {
        return $this->getPaginationUrl($response->getFirstUrl(), get_class($response));
    }

    /**
     * @param mixed $response
     *
     * @return mixed|null
     */
    public function getPaginationLast($response)
    {
        return $this->getPaginationUrl($response->getLastUrl(), get_class($response));
    }

    /**
     * @param string $url
     * @param string $class
     *
     * @return mixed|null
     */
    private function getPaginationUrl($url, $class)
    {
        Assert::stringNotEmpty($class);

        if (empty($url)) {
            return;
        }

        $response = $this->httpGet($url);

        return $this->safeDeserialize($response, $class);
    }
}
