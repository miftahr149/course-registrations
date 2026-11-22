<?php

namespace App\Services;

/**
 * One weekly meeting: a day (1 = Monday .. 7 = Sunday) and a time range ("HH:MM:SS").
 */
final readonly class TimeSlot
{
    public function __construct(
        public int $dayOfWeek,
        public string $startTime,
        public string $endTime,
    ) {}

    /**
     * Two slots clash when they are on the same day and their times overlap.
     * Touching slots (one ends at 10:00, the next starts at 10:00) do not clash.
     */
    public function overlaps(TimeSlot $other): bool
    {
        return $this->dayOfWeek === $other->dayOfWeek
            && $this->normalized($this->startTime) < $this->normalized($other->endTime)
            && $this->normalized($other->startTime) < $this->normalized($this->endTime);
    }

    /**
     * "8:00" and "08:00:00" must compare equal, so pad to HH:MM:SS before comparing strings.
     */
    private function normalized(string $time): string
    {
        $parts = array_map('intval', explode(':', $time)) + [0, 0, 0];

        return sprintf('%02d:%02d:%02d', $parts[0], $parts[1], $parts[2]);
    }
}
