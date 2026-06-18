<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Services\PesepayClient;
use PHPUnit\Framework\TestCase;

class PesepayEncryptionTest extends TestCase
{
    private function client(): PesepayClient
    {
        // 32-char key → AES-256; IV = first 16 bytes.
        return new PesepayClient(
            'integration-key',
            '0123456789abcdef0123456789abcdef',
            'https://api.pesepay.com/api/payments-engine',
            []
        );
    }

    public function test_encrypt_then_decrypt_round_trips(): void
    {
        $data = ['referenceNumber' => 'R1', 'transactionStatus' => 'SUCCESS', 'amount' => 10.5];

        $encrypted = $this->client()->encrypt($data);

        $this->assertNotSame(json_encode($data), $encrypted);
        $this->assertSame($data, $this->client()->decrypt($encrypted));
    }

    public function test_decrypt_of_tampered_payload_throws(): void
    {
        $this->expectException(PaymentGatewayException::class);

        $this->client()->decrypt('!!!not-a-valid-ciphertext!!!');
    }

    public function test_encrypt_without_key_throws(): void
    {
        $this->expectException(PaymentGatewayException::class);

        (new PesepayClient('int', null, 'https://x', []))->encrypt(['a' => 1]);
    }
}
