<?php
declare(strict_types=1);

class EmailSenderPhpMail implements EmailSenderInterface
{
    private const CONTENT_TYPE_TEXT_PLAIN = "plain";
    private const CONTENT_TYPE_TEXT_HTML = "html";

    /**
     * @throws \LogicException if no recipient or subject is set
     */
    public function send(Email $email): bool
    {
        if (empty($email->getTos())) {
            throw new \LogicException('At least one recipient is required.');
        }
        if ($email->getSubject() === '') {
            throw new \LogicException('Subject is required.');
        }

        $tos     = $this->formatAddressList($email->getTos());
        $subject = $this->encodeHeader($email->getSubject());
        [$contentHeaders, $body] = $this->buildContent($email);
        $headers = $this->buildHeaders($email, $contentHeaders);

        return mail($tos, $subject, $body, $headers);
    }

    // --- Private helpers ---

    private function formatAddress(array $address): string
    {
        $email = $address['email'];
        $name = $address['name'];
        return $name !== '' ? $this->encodeHeader($name) . ' <' . $email . '>' : $email;
    }

    /**
     * @param array<array{email: string, name: string}> $addresses
     */
    private function formatAddressList(array $addresses): string
    {
        return implode(', ', array_map(
            fn($a) => $this->formatAddress($a),
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
    private function buildHeaders(Email $email, array $contentHeaders): string
    {
        $lines = [];

        $address = $email->getFrom();
        if ($address['email'] !== '') {
            $lines[] = 'From: ' . $this->formatAddress($address);
        }
        $address = $email->getReplyTo();
        if ($address['email'] !== '') {
            $lines[] = 'Reply-To: ' . $this->formatAddress($address);
        }
        if (!empty($email->getCcs())) {
            $lines[] = 'Cc: ' . $this->formatAddressList($email->getCcs());
        }
        if (!empty($email->getBccs())) {
            $lines[] = 'Bcc: ' . $this->formatAddressList($email->getBccs());
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
    private function buildContent(Email $email): array
    {
        $hasHtml        = $email->getHtmlBody() !== '';
        $hasAttachments = !empty($email->getAttachments());

        if (!$hasHtml && !$hasAttachments) {
            return [
                [
                    $this->getContentTypeText(self::CONTENT_TYPE_TEXT_PLAIN),
                    $this->getContentTransferEncoding(),
                ],
                $this->getEncodedContent($email->getTextBody()),
            ];
        }

        if ($hasHtml && !$hasAttachments) {
            $boundary = $this->newBoundary();
            return [
                ["Content-Type: multipart/alternative; boundary=\"$boundary\""],
                $this->buildAlternativeBody($email, $boundary),
            ];
        }

        if (!$hasHtml) {
            // Plain text + attachments
            $boundary = $this->newBoundary();
            $body  = "--$boundary\r\n";
            $body .= $this->getContentTypeText(self::CONTENT_TYPE_TEXT_PLAIN) . "\r\n";
            $body .= $this->getContentTransferEncoding() . "\r\n\r\n";
            $body .= $this->getEncodedContent($email->getTextBody());
            $body .= "\r\n";
            $body .= $this->buildAttachmentParts($email, $boundary);
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
        $body .= $this->buildAlternativeBody($email, $altBoundary);
        $body .= "\r\n";
        $body .= $this->buildAttachmentParts($email, $mixedBoundary);
        $body .= "--$mixedBoundary--\r\n";
        return [
            ["Content-Type: multipart/mixed; boundary=\"$mixedBoundary\""],
            $body,
        ];
    }

    private function buildAlternativeBody(Email $email, string $boundary): string
    {
        $plainText = $email->getTextBody() !== '' ? $email->getTextBody() : $this->htmlToText($email->getHtmlBody());

        $body  = "--$boundary\r\n";
        $body .= $this->getContentTypeText(self::CONTENT_TYPE_TEXT_PLAIN) . "\r\n";
        $body .= $this->getContentTransferEncoding() . "\r\n\r\n";
        $body .= $this->getEncodedContent($plainText);
        $body .= "\r\n";
        $body .= "--$boundary\r\n";
        $body .= $this->getContentTypeText(self::CONTENT_TYPE_TEXT_HTML) . "\r\n";
        $body .= $this->getContentTransferEncoding() . "\r\n\r\n";
        $body .= $this->getEncodedContent($email->getHtmlBody());
        $body .= "\r\n";
        $body .= "--$boundary--\r\n";
        return $body;
    }

    private function buildAttachmentParts(Email $email, string $boundary): string
    {
        $parts = '';
        foreach ($email->getAttachments() as $attachment) {
            $encodedName = $this->encodeHeader($attachment['filename']);
            $parts .= "--$boundary\r\n";
            $parts .= "Content-Type: {$attachment['mimeType']}; name=\"$encodedName\"\r\n";
            $parts .= "Content-Disposition: attachment; filename=\"$encodedName\"\r\n";
            $parts .= $this->getContentTransferEncoding() . "\r\n\r\n";
            $parts .= $this->getEncodedContent($attachment['data']);
        }
        return $parts;
    }

    private function getContentTypeText(string $textType): string
    {
        return "Content-Type: text/" . $textType . "; charset=\"utf-8\"";
    }

    private function getEncodedContent(string $content): string
    {
        return chunk_split(base64_encode($content));
    }

    private function getContentTransferEncoding(): string
    {
        return "Content-Transfer-Encoding: base64";
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
