<?php

namespace Tests\Unit;

use App\Enums\Impact;
use App\Enums\TicketPriority;
use App\Enums\Urgency;
use PHPUnit\Framework\TestCase;

class TicketPriorityTest extends TestCase
{
    public function test_priority_matrix_maps_impact_and_urgency_to_p1_p4(): void
    {
        $matrix = [
            [Impact::High, Urgency::High, TicketPriority::P1],
            [Impact::High, Urgency::Medium, TicketPriority::P2],
            [Impact::Medium, Urgency::High, TicketPriority::P2],
            [Impact::High, Urgency::Low, TicketPriority::P3],
            [Impact::Medium, Urgency::Medium, TicketPriority::P3],
            [Impact::Low, Urgency::High, TicketPriority::P3],
            [Impact::Medium, Urgency::Low, TicketPriority::P4],
            [Impact::Low, Urgency::Medium, TicketPriority::P4],
            [Impact::Low, Urgency::Low, TicketPriority::P4],
        ];

        foreach ($matrix as [$impact, $urgency, $expected]) {
            $this->assertSame(
                $expected,
                TicketPriority::fromImpactUrgency($impact, $urgency),
                "Impact {$impact->name} × Urgency {$urgency->name}",
            );
        }
    }

    public function test_only_p1_is_critical(): void
    {
        $this->assertTrue(TicketPriority::P1->isCritical());
        $this->assertFalse(TicketPriority::P2->isCritical());
        $this->assertFalse(TicketPriority::P3->isCritical());
        $this->assertFalse(TicketPriority::P4->isCritical());
    }

    public function test_default_targets_match_the_sla_table(): void
    {
        $this->assertSame([15, 120], [
            TicketPriority::P1->firstResponseTargetMinutes(),
            TicketPriority::P1->resolutionTargetMinutes(),
        ]);

        $this->assertSame([30, 240], [
            TicketPriority::P2->firstResponseTargetMinutes(),
            TicketPriority::P2->resolutionTargetMinutes(),
        ]);

        $this->assertSame([120, 480], [
            TicketPriority::P3->firstResponseTargetMinutes(),
            TicketPriority::P3->resolutionTargetMinutes(),
        ]);

        $this->assertSame([480, 1440], [
            TicketPriority::P4->firstResponseTargetMinutes(),
            TicketPriority::P4->resolutionTargetMinutes(),
        ]);
    }

    public function test_options_returns_all_priorities(): void
    {
        $this->assertSame(['P1', 'P2', 'P3', 'P4'], array_keys(TicketPriority::options()));
    }
}
