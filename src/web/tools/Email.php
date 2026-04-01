<?php
declare(strict_types=1);

class Email
{
    private string $from     = '';
    private string $fromName = '';
    private string $subject  = '';
    private string $textBody = '';
    private string $htmlBody = '';
    private string $replyTo     = '';
    private string $replyToName = '';

    /** @var array<array{email: string, name: string}> */
    private array $to  = [];
    /** @var array<array{email: string, name: string}> */
    private array $cc  = [];
    /** @var array<array{email: string, name: string}> */
    private array $bcc = [];

    /** @var array<array{data: string, filename: string, mimeType: string}> */
    private array $attachments = [];

    public function setFrom(string $email, string $name = ''): static
    {
        $this->from     = $email;
        $this->fromName = $name;
        return $this;
    }

    public function getFrom(): array
    {
        return ['email' => $this->from, 'name' => $this->fromName];
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setTextBody(string $text): static
    {
        $this->textBody = $text;
        return $this;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function setHtmlBody(string $html): static
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function setReplyTo(string $email, string $name = ''): static
    {
        $this->replyTo     = $email;
        $this->replyToName = $name;
        return $this;
    }

    public function getReplyTo(): array
    {
        return ['email' => $this->replyTo, 'name' => $this->replyToName];
    }

    public function addTo(string $email, string $name = ''): static
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function getTos(): array
    {
        return $this->to;
    }

    public function addCc(string $email, string $name = ''): static
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function getCcs(): array
    {
        return $this->cc;
    }

    public function addBcc(string $email, string $name = ''): static
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function getBccs(): array
    {
        return $this->bcc;
    }

    /**
     * Add an attachment from a file path.
     *
     * @throws \RuntimeException if the file cannot be read
     */
    public function addAttachmentFile(string $filePath, string $filename = '', string $mimeType = 'application/octet-stream'): static
    {
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new \RuntimeException("Could not read file: $filePath");
        }
        $this->attachments[] = [
            'data'     => $data,
            'filename' => $filename !== '' ? $filename : basename($filePath),
            'mimeType' => $mimeType,
        ];
        return $this;
    }

    /**
     * Add an attachment from raw binary data.
     */
    public function addAttachment(string $data, string $filename, string $mimeType = 'application/octet-stream'): static
    {
        $this->attachments[] = ['data' => $data, 'filename' => $filename, 'mimeType' => $mimeType];
        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
