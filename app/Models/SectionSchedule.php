<?php

namespace App\Models;

use App\Enums\MeetingType;
use Database\Factories\SectionScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MeetingType $meeting_type
 */
#[Fillable(['course_section_id', 'day_of_week', 'start_time', 'end_time', 'meeting_type', 'room'])]
class SectionSchedule extends Model
{
    /** @use HasFactory<SectionScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'meeting_type' => MeetingType::class,
        ];
    }

    /**
     * @return BelongsTo<CourseSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /**
     * A short human-readable form, e.g. "Mon 08:00-10:30 (lecture, R-101)".
     */
    public function label(): string
    {
        $day = [1 => 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$this->day_of_week];

        $details = implode(', ', array_filter([$this->meeting_type->value, $this->room]));

        return sprintf('%s %s-%s (%s)', $day, substr($this->start_time, 0, 5), substr($this->end_time, 0, 5), $details);
    }
}
