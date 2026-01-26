<?php
/**
 * Created by PhpStorm.
 * User: Matt
 */

namespace Freshdesk\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;

/**
 * General Exception
 *
 * Thrown when the Freshdesk API returns an HTTP error code that isn't handled by other exceptions
 *
 * @package Exceptions
 * @author Matthew Clarkson <mpclarkson@gmail.com>
 */
class ApiException extends Exception
{
    protected ?string $body = null;

    /**
     * @internal
     * @return AccessDeniedException|ApiException|AuthenticationException|ConflictingStateException|MethodNotAllowedException|NotFoundException|RateLimitExceededException|UnsupportedAcceptHeaderException|UnsupportedContentTypeException|ValidationException
     */
    public static function create(RequestException $e): self
    {
         if($response = $e->getResponse()) {
             $body = $response->getBody()->getContents();

             switch ($response->getStatusCode()) {
                 case 400:
                     return new ValidationException($e, $body);
                 case 401:
                     return new AuthenticationException($e, $body);
                 case 403:
                     return new AccessDeniedException($e, $body);
                 case 404:
                     return new NotFoundException($e, $body);
                 case 405:
                     return new MethodNotAllowedException($e, $body);
                 case 406:
                     return new UnsupportedAcceptHeaderException($e, $body);
                 case 409:
                     return new ConflictingStateException($e, $body);
                 case 415:
                     return new UnsupportedContentTypeException($e, $body);
                 case 429:
                     return new RateLimitExceededException($e, $body);
             }
         }

         return new ApiException($e);
    }

    /**
     * Returns the Request Exception
     *
     * A Guzzle Request Exception is returned
     *
     * @return RequestException
     */
    public function getRequestException()
    {
        return $this->exception;
    }

    /**
     * @return string|null
     */
    public function getRequestBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getRequestArray()
    {
        return json_decode((string) $this->body, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Exception constructor
     *
     * Constructs a new exception.
     *
     * @param RequestException $exception
     * @param null|string $body
     * @internal
     */
    public function __construct(/**
     * @internal
     */
    private readonly RequestException $exception, ?string $body = null)
    {
        if ($body) {
            $this->body = $body;
        }
        parent::__construct();
    }
}
