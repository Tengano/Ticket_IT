<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    public function test_new_ticket_can_start_processing_but_not_be_resolved_directly(): void
    {
        $this->assertTrue(TicketStatus::New->canTransitionTo(TicketStatus::InProgress));
        $this->assertTrue(TicketStatus::New->canTransitionTo(TicketStatus::Assigned));
        $this->assertFalse(TicketStatus::New->canTransitionTo(TicketStatus::Resolved));
        $this->assertFalse(TicketStatus::New->canTransitionTo(TicketStatus::Reopened));
    }

    public function test_closed_ticket_can_only_be_reopened(): void
    {
        $this->assertTrue(TicketStatus::Closed->canTransitionTo(TicketStatus::Reopened));
        $this->assertFalse(TicketStatus::Closed->canTransitionTo(TicketStatus::InProgress));
        $this->assertFalse(TicketStatus::Closed->canTransitionTo(TicketStatus::Resolved));
    }

    public function test_resolved_ticket_can_be_closed_or_reopened(): void
    {
        $this->assertTrue(TicketStatus::Resolved->canTransitionTo(TicketStatus::Closed));
        $this->assertTrue(TicketStatus::Resolved->canTransitionTo(TicketStatus::Reopened));
        $this->assertFalse(TicketStatus::Resolved->canTransitionTo(TicketStatus::InProgress));
    }

    public function test_waiting_ticket_can_resume_processing(): void
    {
        $this->assertTrue(TicketStatus::WaitingOnUser->canTransitionTo(TicketStatus::InProgress));
        $this->assertTrue(TicketStatus::WaitingOnVendor->canTransitionTo(TicketStatus::InProgress));
    }

    public function test_open_flags(): void
    {
        $this->assertTrue(TicketStatus::New->isOpen());
        $this->assertTrue(TicketStatus::InProgress->isOpen());
        $this->assertTrue(TicketStatus::WaitingOnUser->isOpen());
        $this->assertFalse(TicketStatus::Resolved->isOpen());
        $this->assertFalse(TicketStatus::Closed->isOpen());
        $this->assertTrue(TicketStatus::Closed->isClosed());
    }

    public function test_options_are_vietnamese_labels(): void
    {
        $options = TicketStatus::options();

        $this->assertSame('Mới', $options['new']);
        $this->assertSame('Đang xử lý', $options['in_progress']);
        $this->assertSame('Đã đóng', $options['closed']);
    }
}
