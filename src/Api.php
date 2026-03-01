<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 20/04/2016
 * Time: 2:32 PM
 */

namespace Freshdesk;

use Freshdesk\Exceptions\AccessDeniedException;
use Freshdesk\Exceptions\ApiException;
use Freshdesk\Exceptions\AuthenticationException;
use Freshdesk\Exceptions\ConflictingStateException;
use Freshdesk\Exceptions\RateLimitExceededException;
use Freshdesk\Exceptions\UnsupportedContentTypeException;
use Freshdesk\Resources\Agent;
use Freshdesk\Resources\BusinessHour;
use Freshdesk\Resources\CannedResponses;
use Freshdesk\Resources\CannedResponseFolders;
use Freshdesk\Resources\Category;
use Freshdesk\Resources\Comment;
use Freshdesk\Resources\Company;
use Freshdesk\Resources\Contact;
use Freshdesk\Resources\Conversation;
use Freshdesk\Resources\EmailConfig;
use Freshdesk\Resources\Forum;
use Freshdesk\Resources\Group;
use Freshdesk\Resources\Product;
use Freshdesk\Resources\SLAPolicy;
use Freshdesk\Resources\Ticket;
use Freshdesk\Resources\Attachment;
use Freshdesk\Resources\TimeEntry;
use Freshdesk\Resources\Topic;
use Freshdesk\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class for interacting with the Freshdesk Api
 *
 * This is the only class that should be instantiated directly. All API resources are available
 * via the relevant public properties
 *
 * @package Api
 * @author Matthew Clarkson <mpclarkson@gmail.com>
 * @author Miroslav Koula <mkoula@gmail.com>
 */
class Api
{
    /**
     * Agent resources
     *
     * @api
     * @var Agent
     */
    public $agents;

    /**
     * Company resources
     *
     * @api
     * @var Company
     */
    public $companies;

    /**
     * Contact resources
     *
     * @api
     * @var Contact
     */
    public $contacts;

    /**
     * Group resources
     *
     * @api
     * @var Group
     */
    public $groups;

    /**
     * Ticket resources
     *
     * @api
     * @var Ticket
     */
    public $tickets;

    /**
     * Attachment resources
     *
     * @api
     * @var Attachment
     */
    public $attachments;

    /**
     * TimeEntry resources
     *
     * @api
     * @var TimeEntry
     */
    public $timeEntries;

    /**
     * Conversation resources
     *
     * @api
     * @var Conversation
     */
    public $conversations;

    /**
     * Category resources
     *
     * @api
     * @var Category
     */
    public $categories;

    /**
     * Forum resources
     *
     * @api
     * @var Forum
     */
    public $forums;

    /**
     * Topic resources
     *
     * @api
     * @var Topic
     */
    public $topics;

    /**
     * Comment resources
     *
     * @api
     * @var Comment
     */
    public $comments;

    //Admin

    /**
     * Email Config resources
     *
     * @api
     * @var EmailConfig
     */
    public $emailConfigs;

    /**
     * Access Product resources
     *
     * @api
     * @var Product
     */
    public $products;

    /**
     * Business Hours resources
     *
     * @api
     * @var BusinessHour
     */
    public $businessHours;

    /**
     * SLA Policy resources
     *
     * @api
     * @var SLAPolicy
     */
    public $slaPolicies;

    /**
     * Canned Responses resources
     *
     * @api
     * @var CannedResponses
     */
    public $cannedResponses;

    /**
     * Canned Response Folders resources
     *
     * @api
     * @var CannedResponseFolders
     */
    public $cannedResponseFolders;

    /**
     * @internal
     * @var Client
     */
    protected $client;

    /**
     * @internal
     */
    private readonly string $baseUrl;

    private readonly ?ApiLogger $logger;

    /**
     * Constructs a new api instance
     *
     * @api
     * @param string $apiKey
     * @param string $domain
     * @param ApiLogger|null $logger
     * @throws Exceptions\InvalidConfigurationException
     */
    public function __construct($apiKey, $domain, ?ApiLogger $logger = null)
    {
        $this->validateConstructorArgs($apiKey, $domain);

        $this->baseUrl = sprintf('https://%s.freshdesk.com/api/v2', $domain);
        $this->logger = $logger;

        $this->client = new Client([
                'auth' => [$apiKey, 'X']
            ]
        );

        $this->setupResources();
    }


    /**
     * Internal method for handling requests
     *
     * @internal
     * @param $method
     * @param $endpoint
     * @param array|null $data
     * @param array|null $query
     * @return mixed|null
     * @throws ApiException
     * @throws ConflictingStateException
     * @throws RateLimitExceededException
     * @throws UnsupportedContentTypeException
     */
    public function request(string $method, string $endpoint, ?array $data = null, ?array $query = null): mixed
    {

		if (isset($data['attachments'])) {
			// Has file attachments, so we can't use the json property.
			// Instead, we have to use the "multipart" property
			$attachments = $data['attachments'];
			unset($data['attachments']);

			if (!is_array($attachments)) {
				$attachments = [$attachments];
			}

			$multiparts = [];

			foreach($attachments as $possibleFilename => $attachment) {

			    $multipart = [
					'name' => 'attachments[]',
					'contents' => $attachment, // $attachment is a resource from fopen('/path/to/file', 'r')
				];

			    if (\is_string($possibleFilename)) {
			        $multipart['filename'] = $possibleFilename;
                }

			    $multiparts[] = $multipart;
			}

			// custom fields can not be array
            if (isset($data['custom_fields'])) {
                $customFields = $data['custom_fields'];
                unset($data['custom_fields']);

                foreach ($customFields as $customFieldKey => $customFieldValue) {
                    $multipart = [
                        'name' => sprintf('custom_fields[%s]', $customFieldKey),
                        // boolean needs to be cast to string
                        'contents' => (is_bool($customFieldValue) ? ($customFieldValue === true ? 'true' : 'false') : $customFieldValue),
                    ];

                    $multiparts[] = $multipart;
                }
            }

			foreach($data as $key => $value) {
			    if (is_array($value)) {
			        foreach ($value as $itemValue) {
                        $multiparts[] = [
                            'name' => $key . '[]',
                            'contents' => $itemValue,
                        ];
                    }
                } else {
                    $multiparts[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
			}

			$options = [
				'multipart' => $multiparts,
			];
		} else {
			// normal method
			$options = [
				'json' => $data,
			];
		}

        if (isset($query)) {
            $options['query'] = $query;
        }

        $url = $this->baseUrl . $endpoint;

        $this->logger?->logRequest($method, $endpoint, $data);

        try {
            $result = $this->performRequest($method, $url, $options);
            $this->logger?->logResponse($method, $endpoint, $result);
            return $result;
        } catch (\Throwable $e) {
            $this->logger?->logErrorResponse($method, $endpoint, $e);
            throw $e;
        }
    }

    /**
     * Performs the request
     *
     * @internal
     *
     * @param $method
     * @param $url
     * @param $options
     * @return mixed|null
     * @throws AccessDeniedException
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ConflictingStateException
     */
    private function performRequest($method, $url, $options) {

        try {
            $response = match ($method) {
                'GET' => $this->client->get($url, $options),
                'POST' => $this->client->post($url, $options),
                'PUT' => $this->client->put($url, $options),
                'DELETE' => $this->client->delete($url, $options),
                default => null,
            };

            if ($response === null) {
                return null;
            }

            $body = (string) $response->getBody();
            if ($body === '') {
                return null;
            }

            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (RequestException $e) {
            throw ApiException::create($e);
        }
    }


    /**
     * @param $apiKey
     * @param $domain
     * @throws Exceptions\InvalidConfigurationException
     * @internal
     *
     */
    private function validateConstructorArgs($apiKey, $domain)
    {
        if (!isset($apiKey)) {
            throw new Exceptions\InvalidConfigurationException("API key is empty.");
        }

        if (!isset($domain)) {
            throw new Exceptions\InvalidConfigurationException("Domain is empty.");
        }
    }

    /**
     * @internal
     */
    private function setupResources()
    {
        //People
        $this->agents = new Agent($this);
        $this->companies = new Company($this);
        $this->contacts = new Contact($this);
        $this->groups = new Group($this);

        //Tickets
        $this->tickets = new Ticket($this);
        $this->timeEntries = new TimeEntry($this);
        $this->conversations = new Conversation($this);

        //Attachments
        $this->attachments = new Attachment($this);

        //Discussions
        $this->categories = new Category($this);
        $this->forums = new Forum($this);
        $this->topics = new Topic($this);
        $this->comments = new Comment($this);

        //Admin
        $this->products = new Product($this);
        $this->emailConfigs = new EmailConfig($this);
        $this->slaPolicies = new SLAPolicy($this);
        $this->businessHours = new BusinessHour($this);

        //Canned Responses
        $this->cannedResponses = new CannedResponses($this);
        $this->cannedResponseFolders = new CannedResponseFolders($this);
    }
}
