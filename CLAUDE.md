# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

PHP SDK for the [Freshdesk API v2](https://developers.freshdesk.com/api/). Originally forked from `mpclarkson/freshdesk-php-sdk`, maintained by SeyVillas with additional features like attachment uploads. Used by the SeyVillas backend/ project.

## Commands

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/Resources/TicketTest.php

# Run rector for code modernization (PHP 8.2+)
vendor/bin/rector process

# Preview rector changes without applying
vendor/bin/rector process --dry-run

# Build documentation (generates docs/ from PHPDoc)
./builddocs
```

## Architecture

### Core Classes

- **`Api`** (`src/Api.php`) - Main entry point. Instantiate with API key and domain. All resources are accessed via public properties (`$api->tickets`, `$api->contacts`, etc.)
- **`AbstractResource`** (`src/Resources/AbstractResource.php`) - Base class for all API resources, holds the API reference and endpoint configuration

### Resource Pattern

Each Freshdesk resource extends `AbstractResource` and uses traits for common CRUD operations:

```
src/Resources/
├── Traits/
│   ├── AllTrait.php      # all() - list resources
│   ├── ViewTrait.php     # view($id) - get single resource
│   ├── CreateTrait.php   # create($data) - create resource
│   ├── UpdateTrait.php   # update($id, $data) - update resource
│   ├── DeleteTrait.php   # delete($id) - delete resource
│   └── MonitorTrait.php  # monitor/unmonitor for forums
└── [Resource].php
```

Resources compose traits to expose only supported operations. Custom resource-specific methods are added directly to the class (e.g., `Ticket::restore()`, `Ticket::search()`).

### Exception Handling

API errors are mapped to specific exception types based on HTTP status codes:

| Status | Exception |
|--------|-----------|
| 400 | `ValidationException` |
| 401 | `AuthenticationException` |
| 403 | `AccessDeniedException` |
| 404 | `NotFoundException` |
| 405 | `MethodNotAllowedException` |
| 406 | `UnsupportedAcceptHeaderException` |
| 409 | `ConflictingStateException` |
| 415 | `UnsupportedContentTypeException` |
| 429 | `RateLimitExceededException` |

All exceptions extend `ApiException` which wraps the Guzzle `RequestException` and provides `getRequestBody()` and `getRequestArray()` for error details.

### Attachment Handling

The `Api::request()` method automatically switches from JSON to multipart/form-data when `attachments` key is present in the data array. Attachments should be file resources from `fopen()`.

## API Implementation Status

Based on the [Freshdesk API v2 documentation](https://developers.freshdesk.com/api/):

### Implemented

| Resource | Property | Operations |
|----------|----------|------------|
| Tickets | `$api->tickets` | all, view, create, update, delete, restore, search, fields, conversations, timeEntries |
| Contacts | `$api->contacts` | all, view, create, update, delete |
| Companies | `$api->companies` | all, view, create, update, delete |
| Agents | `$api->agents` | all, view, create, update, delete, current |
| Groups | `$api->groups` | all, view, create, update, delete |
| Conversations | `$api->conversations` | create (reply/note), update, delete |
| Time Entries | `$api->timeEntries` | all, view, create, update, delete |
| Canned Responses | `$api->cannedResponses` | all, view, create, update |
| Canned Response Folders | `$api->cannedResponseFolders` | all, view, create, update |
| Products | `$api->products` | all, view |
| Email Configs | `$api->emailConfigs` | all, view |
| Business Hours | `$api->businessHours` | all, view |
| SLA Policies | `$api->slaPolicies` | all, view |
| Attachments | `$api->attachments` | delete |
| **Discussions:** | | |
| Categories | `$api->categories` | all, view, create, update, delete |
| Forums | `$api->forums` | all, view, create, update, delete |
| Topics | `$api->topics` | all, view, create, update, delete, monitor, unmonitor |
| Comments | `$api->comments` | all, view, create, update, delete |

### Not Implemented

- **Solutions** (Knowledge Base) - Categories, Folders, Articles
- **Surveys** - List surveys, satisfaction ratings
- **Ticket Fields** - CRUD operations (only list via tickets->fields())
- **Ticket Forms** - CRUD operations
- **Contact Fields** - CRUD operations
- **Company Fields** - CRUD operations
- **Custom Objects** - CRUD operations
- **Skills** - Agent skills management
- **Roles** - Agent roles (view only in API)
- **Email Mailboxes** - CRUD operations (newer than Email Configs)
- **Scenario Automations** - List automations
- **Automation Rules** - CRUD operations
- **Watchers** - Add/remove ticket watchers
- **Field Service Management** - Service tasks, groups, technicians
- **Threads** - Internal messaging threads
- **Omnichannel** - Activities, groups
- **Settings** - Helpdesk settings
- **Account** - View/export account data

## Testing

Tests use PHPUnit 10 and verify class structure and method existence. They do not make real API calls.

Each resource test:
1. Extends `TestCase` (sets up an API instance)
2. Implements `methodsThatShouldExist()` data provider
3. Verifies expected methods exist on the resource class

## Adding New Resources

1. Create resource class in `src/Resources/` extending `AbstractResource`
2. Set `protected $endpoint` to the API path (e.g., `/tickets`)
3. Add appropriate traits for CRUD operations
4. Add any resource-specific methods
5. Register in `Api::setupResources()` and add public property with type hint
6. Create test in `tests/Resources/`
