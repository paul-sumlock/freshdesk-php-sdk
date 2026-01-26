<?php
namespace Freshdesk\tests\Resources;

use Freshdesk\tests\TestCase;
use Freshdesk\Resources\Ticket;

/**
 * Ticket Api tests
 *
 * @author Matthew Clarkson <mpclarkson@gmail.com>
 */
class TicketApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->class = Ticket::class;
    }

    public static function methodsThatShouldExist(): array
    {
        return [
            ['create'],
            ['all'],
            ['view'],
            ['update'],
            ['delete'],
            ['fields'],
            ['restore'],
            ['fields'],
            ['conversations'],
            ['timeEntries'],
            ['search']
        ];
    }
}
