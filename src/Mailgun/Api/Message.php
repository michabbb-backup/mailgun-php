<?php

/*
 * Copyright (C) 2013-2016 Mailgun
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Mailgun\Api;

use Mailgun\Assert;
use Mailgun\Exception\InvalidArgumentException;
use Mailgun\Model\Message\SendResponse;
use Mailgun\Model\Message\ShowResponse;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Message extends HttpApi
{
    /**
     * @param string $domain
     * @param array  $params
     *
     * @return SendResponse
     */
    public function send($domain, array $params)
    {
        Assert::notEmpty($domain);
        Assert::notEmpty($params);

        $meme = '';
        if (!empty($params['message'])) {
            $meme = '.mime';
        }

        $postDataMultipart = [];
        $fields = ['message', 'attachment', 'inline'];
        foreach ($fields as $fieldName) {
            if (!isset($params[$fieldName])) {
                continue;
            }

            Assert::isArray($params[$fieldName]);
            foreach ($params[$fieldName] as $file) {
                $postDataMultipart[] = $this->prepareFile($fieldName, $file);
            }

            unset($params[$fieldName]);
        }

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $postDataMultipart[] = [
                        'name' => $key,
                        'content' => $subValue,
                    ];
                }
            } else {
                $postDataMultipart[] = [
                    'name' => $key,
                    'content' => $value,
                ];
            }
        }

        $response = $this->httpPostRaw(sprintf('/v3/%s/messages%s', $domain, $meme), $postDataMultipart);

        return $this->hydrateResponse($response, SendResponse::class);
    }

    /**
     * Get stored message.
     *
     * @param string $url
     * @param bool   $rawMessage if true we will use "Accept: message/rfc2822" header.
     *
     * @return ShowResponse
     */
    public function show($url, $rawMessage = false)
    {
        Assert::notEmpty($url);

        $headers = [];
        if ($rawMessage) {
            $headers['Accept'] = 'message/rfc2822';
        }

        $response = $this->httpGet($url, [], $headers);

        return $this->hydrateResponse($response, ShowResponse::class);
    }

    /**
     * Prepare a file.
     *
     * @param string $fieldName
     * @param array  $filePath  array('fileContent' => 'content') or array('filePath' => '/foo/bar')
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    private function prepareFile($fieldName, array $filePath)
    {
        $filename = isset($filePath['filename']) ? $filePath['filename'] : null;

        if (isset($filePath['fileContent'])) {
            // File from memory
            $resource = fopen('php://temp', 'r+');
            fwrite($resource, $filePath['fileContent']);
            rewind($resource);
        } elseif (isset($filePath['filePath'])) {
            // File form path
            $path = $filePath['filePath'];

            // Remove leading @ symbol
            if (strpos($path, '@') === 0) {
                $path = substr($path, 1);
            }

            $resource = fopen($path, 'r');
        } else {
            throw new InvalidArgumentException('When using a file you need to specify parameter "fileContent" or "filePath"');
        }

        return [
            'name' => $fieldName,
            'content' => $resource,
            'filename' => $filename,
        ];
    }
}
