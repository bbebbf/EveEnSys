<?php
declare(strict_types=1);

interface EmailSenderInterface
{
    /**
     * @throws \LogicException if no recipient or subject is set
     */
    public function send(Email $email): bool;
}
