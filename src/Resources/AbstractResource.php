<?php
/**
 * Created by PhpStorm.
 * User: Matthew
 * Date: 21/04/2016
 * Time: 8:20 AM
 */

namespace Freshdesk\Resources;

use Freshdesk\Api;

/**
 * Abstract Resource
 *
 * Abstract class which all resources inherit from
 *
 * @internal
 * @package Api\Resources
 */
abstract class AbstractResource
{

    /**
     * @internal
     */
    protected string $endpoint;

    /**
     * Resource constructor
     *
     * Constructs a new resource
     *
     * @param Api $api
     * @internal
     *
     */
    public function __construct(
        /**
         * @internal
         */
        private readonly Api $api
    )
    {
    }

    /**
     * Creates the endpoint
     *
     * @param int|string|null $id The endpoint terminator
     * @return string
     * @internal
     */
    protected function endpoint(int|string|null $id = null): string
    {
        return $id === null ? $this->endpoint : $this->endpoint . '/' . $id;
    }

    /**
     * @return Api
     * @internal
     */
    protected function api()
    {
        return $this->api;
    }
}
