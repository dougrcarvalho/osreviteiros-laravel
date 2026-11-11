<?php

namespace App\Support;

/**
 * Confere senhas no formato do WordPress, para migrar usuários sem exigir nova senha.
 *  - `$wp$2y$…`  (WP 6.8+): bcrypt sobre base64(HMAC-SHA384(senha, "wp-sha384"))
 *  - `$P$…`/`$H$…` (phpass portátil, MD5 iterado)
 *  - MD5 puro (32 hex, WP muito antigo)
 */
class WpPasswordHasher
{
    private const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /** O valor parece um hash do WordPress que sabemos verificar? */
    public static function reconhece(?string $hash): bool
    {
        if (! $hash) {
            return false;
        }

        return str_starts_with($hash, '$wp$2y$')
            || (strlen($hash) === 34 && (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')))
            || (strlen($hash) === 32 && ctype_xdigit($hash));
    }

    public static function confere(string $senha, ?string $hash): bool
    {
        if (! self::reconhece($hash) || $senha === '') {
            return false;
        }

        if (str_starts_with($hash, '$wp$2y$')) {
            $pre = base64_encode(hash_hmac('sha384', $senha, 'wp-sha384', true));

            return password_verify($pre, substr($hash, 3));
        }

        if (strlen($hash) === 32) {
            return hash_equals(strtolower($hash), md5($senha));
        }

        return hash_equals($hash, self::phpass($senha, $hash));
    }

    private static function phpass(string $senha, string $configuracao): string
    {
        $erro = '*0';
        $log2 = strpos(self::ITOA64, $configuracao[3]);

        if ($log2 === false || $log2 < 7 || $log2 > 30) {
            return $erro;
        }

        $contagem = 1 << $log2;
        $sal      = substr($configuracao, 4, 8);

        if (strlen($sal) !== 8) {
            return $erro;
        }

        $h = md5($sal.$senha, true);
        do {
            $h = md5($h.$senha, true);
        } while (--$contagem);

        return substr($configuracao, 0, 12).self::codifica64($h, 16);
    }

    private static function codifica64(string $entrada, int $total): string
    {
        $saida = '';
        $i     = 0;

        do {
            $valor  = ord($entrada[$i++]);
            $saida .= self::ITOA64[$valor & 0x3f];

            if ($i < $total) {
                $valor |= ord($entrada[$i]) << 8;
            }
            $saida .= self::ITOA64[($valor >> 6) & 0x3f];

            if ($i++ >= $total) {
                break;
            }

            if ($i < $total) {
                $valor |= ord($entrada[$i]) << 16;
            }
            $saida .= self::ITOA64[($valor >> 12) & 0x3f];

            if ($i++ >= $total) {
                break;
            }

            $saida .= self::ITOA64[($valor >> 18) & 0x3f];
        } while ($i < $total);

        return $saida;
    }
}
