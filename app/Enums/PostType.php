<?php

namespace App\Enums;

enum PostType: int
{
    case Announcement = 0;
    case Completion = 1;
    case TextAnswer = 2;
    case FileUpload = 3;

    public function label(): string
    {
        return match ($this) {
            self::Announcement => 'No submission required',
            self::Completion => 'Completion checkbox only',
            self::TextAnswer => 'Completion with a text answer',
            self::FileUpload => 'ZIP file submission with grading',
        };
    }

    public function acceptsSubmissions(): bool
    {
        return $this !== self::Announcement;
    }
}
