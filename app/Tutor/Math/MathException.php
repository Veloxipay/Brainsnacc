<?php

namespace App\Tutor\Math;

/** Something the maths engine can't read or work with. The message is safe to show a student. */
class MathException extends \RuntimeException
{
    /** Where in the typed text the problem is (character offset), if known. */
    public ?int $position = null;

    public static function at(string $message, ?int $position): self
    {
        $e = new self($message);
        $e->position = $position;

        return $e;
    }
}
