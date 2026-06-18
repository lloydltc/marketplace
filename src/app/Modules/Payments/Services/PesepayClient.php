<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Pesepay payments engine.
 *
 * Pesepay encrypts every request/response body as a single base64 `payload`
 * field using AES-256-CBC with the 32-char encryption key (IV = first 16 bytes
 * of that key). The integration key authenticates the request. Because only a
 * holder of the encryption key can produce a decryptable payload, successful
 * decryption is itself the signature check on inbound webhooks.
 */
class PesepayClient
{
    public function __construct(
        private readonly ?string $integrationKey,
        private readonly ?string $encryptionKey,
        private readonly string $baseUrl,
        private readonly array $endpoints,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('pesepay.integration_key'),
            config('pesepay.encryption_key'),
            rtrim((string) config('pesepay.base_url'), '/'),
            config('pesepay.endpoints', []),
        );
    }

    // ─── Encryption ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    public function encrypt(array $data): string
    {
        $key = $this->requireKey();
        $iv  = substr($key, 0, 16);

        $encrypted = openssl_encrypt(
            json_encode($data, JSON_THROW_ON_ERROR),
            'AES-256-CBC',
            $key,
            0,
            $iv
        );

        if ($encrypted === false) {
            throw new PaymentGatewayException('Failed to encrypt Pesepay payload.');
        }

        return $encrypted;
    }

    /**
     * @return array<string, mixed>
     */
    public function decrypt(string $payload): array
    {
        $key = $this->requireKey();
        $iv  = substr($key, 0, 16);

        $decrypted = openssl_decrypt($payload, 'AES-256-CBC', $key, 0, $iv);

        if ($decrypted === false) {
            throw new PaymentGatewayException('Failed to decrypt Pesepay payload (bad key or tampered data).');
        }

        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
    }

    // ─── API calls ──────────────────────────────────────────────────────────────

    /**
     * Initiate a hosted-checkout transaction.
     *
     * @return array{referenceNumber: string, redirectUrl: ?string, pollUrl: ?string, raw: array<string,mixed>}
     */
    public function initiateTransaction(
        float $amount,
        string $currency,
        string $reason,
        string $merchantReference,
        string $returnUrl,
        string $resultUrl
    ): array {
        $body = [
            'amountDetails'     => ['amount' => $amount, 'currencyCode' => $currency],
            'merchantReference' => $merchantReference,
            'reasonForPayment'  => $reason,
            'returnUrl'         => $returnUrl,
            'resultUrl'         => $resultUrl,
        ];

        $response = Http::withHeaders([
            'Authorization' => (string) $this->integrationKey,
            'Content-Type'  => 'application/json',
        ])->post($this->url('initiate'), ['payload' => $this->encrypt($body)]);

        if (! $response->successful()) {
            throw new PaymentGatewayException('Pesepay initiation failed: HTTP ' . $response->status());
        }

        $decoded = $this->decrypt((string) data_get($response->json(), 'payload'));

        return [
            'referenceNumber' => (string) ($decoded['referenceNumber'] ?? ''),
            'redirectUrl'     => $decoded['redirectUrl'] ?? null,
            'pollUrl'         => $decoded['pollUrl'] ?? null,
            'raw'             => $decoded,
        ];
    }

    /**
     * Seamless mobile-money payment (EcoCash / InnBucks) — no redirect. The
     * customer approves on their phone; the result arrives via webhook + polling.
     *
     * @param  array{email?: ?string, phoneNumber?: ?string, name?: ?string}  $customer
     * @param  array<string, mixed>  $requiredFields  e.g. ['customerPhoneNumber' => '077...']
     * @return array{referenceNumber: string, pollUrl: ?string, redirectUrl: ?string, raw: array<string,mixed>}
     */
    public function makeSeamlessPayment(
        float $amount,
        string $currency,
        string $reason,
        string $merchantReference,
        string $paymentMethodCode,
        array $customer,
        array $requiredFields,
        string $returnUrl,
        string $resultUrl
    ): array {
        $body = [
            'amountDetails'               => ['amount' => $amount, 'currencyCode' => $currency],
            'currencyCode'                => $currency,
            'merchantReference'           => $merchantReference,
            'reasonForPayment'            => $reason,
            'returnUrl'                   => $returnUrl,
            'resultUrl'                   => $resultUrl,
            'paymentMethodCode'           => $paymentMethodCode,
            'customer'                    => array_filter([
                'email'       => $customer['email'] ?? null,
                'phoneNumber' => $customer['phoneNumber'] ?? null,
                'name'        => $customer['name'] ?? null,
            ], fn ($v) => $v !== null),
            'paymentMethodRequiredFields' => (object) $requiredFields,
        ];

        $response = Http::withHeaders([
            'Authorization' => (string) $this->integrationKey,
            'Content-Type'  => 'application/json',
        ])->post($this->url('make_payment'), ['payload' => $this->encrypt($body)]);

        if (! $response->successful()) {
            throw new PaymentGatewayException('Pesepay seamless payment failed: HTTP ' . $response->status());
        }

        $decoded = $this->decrypt((string) data_get($response->json(), 'payload'));

        return [
            'referenceNumber' => (string) ($decoded['referenceNumber'] ?? ''),
            'pollUrl'         => $decoded['pollUrl'] ?? null,
            'redirectUrl'     => $decoded['redirectUrl'] ?? null,
            'raw'             => $decoded,
        ];
    }

    /**
     * Server-side status check — never trust the browser redirect alone.
     *
     * @return array<string, mixed>
     */
    public function checkStatus(string $referenceNumber): array
    {
        $response = Http::withHeaders([
            'Authorization' => (string) $this->integrationKey,
        ])->get($this->url('check'), ['referenceNumber' => $referenceNumber]);

        if (! $response->successful()) {
            throw new PaymentGatewayException('Pesepay status check failed: HTTP ' . $response->status());
        }

        return $this->decrypt((string) data_get($response->json(), 'payload'));
    }

    // ─── Internals ──────────────────────────────────────────────────────────────

    private function url(string $endpoint): string
    {
        return $this->baseUrl . ($this->endpoints[$endpoint] ?? '');
    }

    private function requireKey(): string
    {
        if (empty($this->encryptionKey)) {
            throw new PaymentGatewayException('Pesepay encryption key is not configured.');
        }

        return $this->encryptionKey;
    }
}
