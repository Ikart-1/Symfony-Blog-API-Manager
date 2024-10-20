<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Attribute;

#[Attribute]
class NoBannedWords extends Constraint
{
    public string $message = 'The content contains banned word "{{ word }}".';
    public array $bannedWords = ['inappropriate', 'offensive', 'vulgar'];
    public function __construct(
        array $bannedWords = null,
        string $message = null,
        array $groups = null,
        $payload = null
    )
    {
        parent::__construct([], $groups, $payload);

        $this->bannedWords = $bannedWords ?? $this->bannedWords;
        $this->message = $message ?? $this->message;
    }
}