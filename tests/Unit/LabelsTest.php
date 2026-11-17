<?php

namespace Tests\Unit;

use App\Support\Labels;
use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    public function test_labels_and_formatting(): void
    {
        $this->assertSame('Davam edir', Labels::label(Labels::TASK_STATUSES, 'in_progress'));
        $this->assertSame('emerald', Labels::color(Labels::TASK_STATUSES, 'done'));
        $this->assertSame('Noyabr 2026', Labels::monthYear(2026, 11));
        $this->assertSame('1.5 KB', Labels::bytes(1536));
    }
}
