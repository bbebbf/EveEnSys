<?php
declare(strict_types=1);

class EmailSenderMailjet implements EmailSenderInterface
{
    private string $apiUrl;
    private string $key;
    private string $secret;
    private string $fromEmail;
    private string $fromName;
    private bool   $sandbox;

    public function __construct(array $config)
    {
        $this->apiUrl    = $config['APIUrl']    ?? 'https://api.mailjet.com/v3.1/send';
        $this->key       = $config['Key']       ?? '';
        $this->secret    = $config['Secret']    ?? '';
        $this->fromEmail = $config['FromEmail'] ?? '';
        $this->fromName  = $config['FromName']  ?? '';
        $this->sandbox   = $config['Sandbox']   ?? true;
    }

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

        $from = $email->getFrom();
        $fromEmail = $from['email'] !== '' ? $from['email'] : $this->fromEmail;
        $fromName  = $from['email'] !== '' ? $from['name']  : $this->fromName;

        $message = [
            'From'    => ['Email' => $fromEmail, 'Name' => $fromName],
            'To'      => $this->buildAddressList($email->getTos()),
            'Subject' => $email->getSubject(),
        ];

        if (!empty($email->getCcs())) {
            $message['Cc'] = $this->buildAddressList($email->getCcs());
        }
        if (!empty($email->getBccs())) {
            $message['Bcc'] = $this->buildAddressList($email->getBccs());
        }

        $replyTo = $email->getReplyTo();
        if ($replyTo['email'] !== '') {
            $message['ReplyTo'] = ['Email' => $replyTo['email'], 'Name' => $replyTo['name']];
        }

        if ($email->getTextBody() !== '') {
            $message['TextPart'] = $email->getTextBody();
        }
        if ($email->getHtmlBody() !== '') {
            $message['HTMLPart'] = $email->getHtmlBody();
        }

        if (!empty($email->getAttachments())) {
            $message['Attachments'] = $this->buildAttachments($email->getAttachments());
        }

        $payload = ['Messages' => [$message]];
        if ($this->sandbox) {
            $payload['SandboxMode'] = true;
        }

        return $this->post($payload);
    }

    /**
     * @param array<array{email: string, name: string}> $addresses
     * @return list<array{Email: string, Name: string}>
     */
    private function buildAddressList(array $addresses): array
    {
        return array_map(
            fn($a) => ['Email' => $a['email'], 'Name' => $a['name']],
            $addresses
        );
    }

    /**
     * @param array<array{data: string, filename: string, mimeType: string}> $attachments
     * @return list<array{ContentType: string, Filename: string, Base64Content: string}>
     */
    private function buildAttachments(array $attachments): array
    {
        return array_map(
            fn($a) => [
                'ContentType'   => $a['mimeType'],
                'Filename'      => $a['filename'],
                'Base64Content' => base64_encode($a['data']),
            ],
            $attachments
        );
    }

    private function post(array $payload): bool
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->key . ':' . $this->secret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        curl_exec($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpStatus === 200;
    }
}
