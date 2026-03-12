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

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function setTextBody(string $text): static
    {
        $this->textBody = $text;
        return $this;
    }

    public function setHtmlBody(string $html): static
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function setReplyTo(string $email, string $name = ''): static
    {
        $this->replyTo     = $email;
        $this->replyToName = $name;
        return $this;
    }

    public function addTo(string $email, string $name = ''): static
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function addCc(string $email, string $name = ''): static
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function addBcc(string $email, string $name = ''): static
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
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

    /**
     * @throws \LogicException if no recipient or subject is set
     */
    public function send(): bool
    {
        if (empty($this->to)) {
            throw new \LogicException('At least one recipient is required.');
        }
        if ($this->subject === '') {
            throw new \LogicException('Subject is required.');
        }

        $to      = $this->formatAddressList($this->to);
        $subject = $this->encodeHeader($this->subject);
        [$contentHeaders, $body] = $this->buildContent();
        $headers = $this->buildHeaders($contentHeaders);

        return mail($to, $subject, $body, $headers);
    }

    // --- Private helpers ---

    private function formatAddress(string $email, string $name): string
    {
        return $name !== '' ? $this->encodeHeader($name) . ' <' . $email . '>' : $email;
    }

    /**
     * @param array<array{email: string, name: string}> $addresses
     */
    private function formatAddressList(array $addresses): string
    {
        return implode(', ', array_map(
            fn($a) => $this->formatAddress($a['email'], $a['name']),
            $addresses
        ));
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    /**
     * @param list<string> $contentHeaders
     */
    private function buildHeaders(array $contentHeaders): string
    {
        $lines = [];

        if ($this->from !== '') {
            $lines[] = 'From: ' . $this->formatAddress($this->from, $this->fromName);
        }
        if ($this->replyTo !== '') {
            $lines[] = 'Reply-To: ' . $this->formatAddress($this->replyTo, $this->replyToName);
        }
        if (!empty($this->cc)) {
            $lines[] = 'Cc: ' . $this->formatAddressList($this->cc);
        }
        if (!empty($this->bcc)) {
            $lines[] = 'Bcc: ' . $this->formatAddressList($this->bcc);
        }

        $lines[] = 'MIME-Version: 1.0';
        foreach ($contentHeaders as $header) {
            $lines[] = $header;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Builds the email content and returns [headerLines, body].
     *
     * @return array{0: list<string>, 1: string}
     */
    private function buildContent(): array
    {
        $hasHtml        = $this->htmlBody !== '';
        $hasAttachments = !empty($this->attachments);

        if (!$hasHtml && !$hasAttachments) {
            return [
                [
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: quoted-printable',
                ],
                quoted_printable_encode($this->textBody),
            ];
        }

        if ($hasHtml && !$hasAttachments) {
            $boundary = $this->newBoundary();
            return [
                ["Content-Type: multipart/alternative; boundary=\"$boundary\""],
                $this->buildAlternativeBody($boundary),
            ];
        }

        if (!$hasHtml) {
            // Plain text + attachments
            $boundary = $this->newBoundary();
            $body  = "--$boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $body .= quoted_printable_encode($this->textBody);
            $body .= "\r\n";
            $body .= $this->buildAttachmentParts($boundary);
            $body .= "--$boundary--\r\n";
            return [
                ["Content-Type: multipart/mixed; boundary=\"$boundary\""],
                $body,
            ];
        }

        // HTML + attachments: multipart/mixed wrapping multipart/alternative
        $mixedBoundary = $this->newBoundary();
        $altBoundary   = $this->newBoundary();
        $body  = "--$mixedBoundary\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"$altBoundary\"\r\n\r\n";
        $body .= $this->buildAlternativeBody($altBoundary);
        $body .= "\r\n";
        $body .= $this->buildAttachmentParts($mixedBoundary);
        $body .= "--$mixedBoundary--\r\n";
        return [
            ["Content-Type: multipart/mixed; boundary=\"$mixedBoundary\""],
            $body,
        ];
    }

    private function buildAlternativeBody(string $boundary): string
    {
        $plainText = $this->textBody !== '' ? $this->textBody : $this->htmlToText($this->htmlBody);

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($plainText);
        $body .= "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($this->htmlBody);
        $body .= "\r\n";
        $body .= "--$boundary--\r\n";
        return $body;
    }

    private function buildAttachmentParts(string $boundary): string
    {
        $parts = '';
        foreach ($this->attachments as $attachment) {
            $encodedName = $this->encodeHeader($attachment['filename']);
            $parts .= "--$boundary\r\n";
            $parts .= "Content-Type: {$attachment['mimeType']}; name=\"$encodedName\"\r\n";
            $parts .= "Content-Disposition: attachment; filename=\"$encodedName\"\r\n";
            $parts .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $parts .= chunk_split(base64_encode($attachment['data']));
        }
        return $parts;
    }

    private function htmlToText(string $html): string
    {
        $text = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>'],
            "\n",
            $html
        );
        $text = strip_tags($text);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function newBoundary(): string
    {
        return '----=_Part_' . bin2hex(random_bytes(8));
    }
}
