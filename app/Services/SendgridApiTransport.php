<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class SendgridApiTransport extends AbstractTransport
{
    protected Client $client;
    protected string $key;

    public function __construct(string $apiKey)
    {
        $this->key = $apiKey;
        $this->client = new Client(['base_uri' => 'https://api.sendgrid.com/v3/']);
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = $this->buildPayload($email);
        $this->client->post('mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
    }

    protected function buildPayload(Email $email): array
    {
        $from = $email->getFrom();
        $to = $email->getTo();
        $payload = [
            'personalizations' => [],
            'from' => ['email' => $from[0]->getAddress(), 'name' => $from[0]->getName()],
            'subject' => $email->getSubject() ?? '',
            'content' => [],
        ];

        foreach ($to as $addr) {
            $payload['personalizations'][] = [
                'to' => [['email' => $addr->getAddress(), 'name' => $addr->getName()]],
            ];
        }

        if ($html = $email->getHtmlBody()) {
            $payload['content'][] = ['type' => 'text/html', 'value' => $html];
        } elseif ($text = $email->getTextBody()) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => $text];
        }

        return $payload;
    }

    public function __toString(): string
    {
        return 'sendgrid-api';
    }
}