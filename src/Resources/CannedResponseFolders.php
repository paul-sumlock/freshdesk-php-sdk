<?php

/**
 * @author    SeyVillas GmbH
 * @copyright Copyright (c) 2023 SeyVillas GmbH, Kopernikusstr. 24, 10245 Berlin, https://www.seyvillas.com
 * @created   11/04/23 12:32
 */

declare(strict_types=1);

namespace Freshdesk\Resources;

use Freshdesk\Resources\Traits\AllTrait;
use Freshdesk\Resources\Traits\CreateTrait;
use Freshdesk\Resources\Traits\DeleteTrait;
use Freshdesk\Resources\Traits\UpdateTrait;
use Freshdesk\Resources\Traits\ViewTrait;

/**
 * Ticket resource
 *
 * Provides access to ticket resources
 *
 * @package Api\Resources
 */
class CannedResponseFolders extends AbstractResource
{

    use AllTrait, CreateTrait, ViewTrait, UpdateTrait, DeleteTrait;

    /**
     * The resource endpoint
     *
     * @var string
     */
    protected string $endpoint = '/canned_response_folders';

    /**
     * List ticket fields
     *
     * The agent whose credentials (API key or username/password) are being used to make this API call should be
     * authorised to view the ticket fields
     *
     * @param array|null $query
     * @return mixed|null
     * @throws \Freshdesk\Exceptions\AccessDeniedException
     * @throws \Freshdesk\Exceptions\ApiException
     * @throws \Freshdesk\Exceptions\AuthenticationException
     * @throws \Freshdesk\Exceptions\ConflictingStateException
     * @throws \Freshdesk\Exceptions\NotFoundException
     * @throws \Freshdesk\Exceptions\RateLimitExceededException
     * @throws \Freshdesk\Exceptions\UnsupportedContentTypeException
     * @throws \Freshdesk\Exceptions\MethodNotAllowedException
     * @throws \Freshdesk\Exceptions\UnsupportedAcceptHeaderException
     * @throws \Freshdesk\Exceptions\ValidationException
     */
    public function fields(?array $query = null)
    {
        return $this->api()->request('GET', '/ticket_fields', null, $query);
    }

    /**
     * Filters by ticket fields
     *
     * Make sure to pass a valid $filtersQuery string example: "type:question"
     *
     * @api
     * @param string $filtersQuery
     * @return array|null
     * @throws \Freshdesk\Exceptions\AccessDeniedException
     * @throws \Freshdesk\Exceptions\ApiException
     * @throws \Freshdesk\Exceptions\AuthenticationException
     * @throws \Freshdesk\Exceptions\ConflictingStateException
     * @throws \Freshdesk\Exceptions\NotFoundException
     * @throws \Freshdesk\Exceptions\RateLimitExceededException
     * @throws \Freshdesk\Exceptions\UnsupportedContentTypeException
     * @throws \Freshdesk\Exceptions\MethodNotAllowedException
     * @throws \Freshdesk\Exceptions\UnsupportedAcceptHeaderException
     * @throws \Freshdesk\Exceptions\ValidationException
     */
    public function search(string $filtersQuery)
    {
        $end = '/search'.$this->endpoint();
        $query = [
            'query' => '"'.$filtersQuery.'"',
        ];
        return $this->api()->request('GET', $end, null, $query);
    }
}
