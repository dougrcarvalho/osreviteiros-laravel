<?php

namespace Tests\Unit;

use App\Support\WpPasswordHasher;
use PHPUnit\Framework\TestCase;

class WpPasswordHasherTest extends TestCase
{
    public function test_phpass_vetor_oficial(): void
    {
        $this->assertTrue(WpPasswordHasher::confere('test12345', '$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0'));
        $this->assertFalse(WpPasswordHasher::confere('errada', '$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0'));
    }

    public function test_wp_2y_bcrypt_com_prehash(): void
    {
        $pre  = base64_encode(hash_hmac('sha384', 'minha senha', 'wp-sha384', true));
        $hash = '$wp'.password_hash($pre, PASSWORD_BCRYPT, ['cost' => 4]);

        $this->assertStringStartsWith('$wp$2y$', $hash);
        $this->assertTrue(WpPasswordHasher::confere('minha senha', $hash));
        $this->assertFalse(WpPasswordHasher::confere('outra', $hash));
    }

    public function test_md5_antigo_e_formatos_desconhecidos(): void
    {
        $this->assertTrue(WpPasswordHasher::confere('abc', md5('abc')));
        $this->assertFalse(WpPasswordHasher::confere('abc', 'lixo'));
        $this->assertFalse(WpPasswordHasher::confere('abc', null));
        $this->assertFalse(WpPasswordHasher::reconhece('$2y$10$bcryptdolaravel'));
    }
}
