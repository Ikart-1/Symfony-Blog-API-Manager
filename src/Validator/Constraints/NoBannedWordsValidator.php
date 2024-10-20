<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Psr\Log\LoggerInterface;

class NoBannedWordsValidator extends ConstraintValidator
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoBannedWords) {
            throw new UnexpectedTypeException($constraint, NoBannedWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $text = strtolower($value);
        $this->logger->debug('Validating text for banned words', [
            'text' => $text,
            'bannedWords' => $constraint->bannedWords
        ]);

        foreach ($constraint->bannedWords as $word) {
            $word = strtolower($word);
            if (str_contains($text, $word)) {
                $this->logger->debug('Found banned word', ['word' => $word]);
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ word }}', $word)
                    ->addViolation();
                return;
            }
        }
    }
}