<?php

namespace App\Services\Importacao;

use Generator;

/**
 * Lê dumps do phpMyAdmin/mysqldump (`INSERT INTO t (cols) VALUES (...),(...);`) sem carregar
 * o arquivo inteiro e devolve cada linha como array coluna → valor (NULL vira null).
 * Só entende INSERT de linhas literais (o que um dump de tabela gera).
 */
class SqlDumpReader
{
    /** @return Generator<int, array<string, string|int|float|null>> */
    public function linhas(string $arquivo, string $tabela): Generator
    {
        $h = fopen($arquivo, 'rb') ?: throw new \RuntimeException("Não foi possível abrir {$arquivo}");
        $colunas = null;
        $buffer  = '';

        try {
            while (($linha = fgets($h)) !== false) {
                if ($colunas === null) {
                    if (preg_match('/^INSERT INTO `?'.preg_quote($tabela, '/').'`?\s*\((.*?)\)\s*VALUES\s*$/i', rtrim($linha), $m)) {
                        $colunas = array_map(fn ($c) => trim($c, " `"), explode(',', $m[1]));
                    }

                    continue;
                }

                $buffer .= $linha;

                // Tupla completa = parênteses balanceados fora de strings
                if (! $this->completa($buffer)) {
                    continue;
                }

                $valores = $this->tupla(trim($buffer));
                $buffer  = '';

                if ($valores !== null && count($valores) === count($colunas)) {
                    yield array_combine($colunas, $valores);
                }

                if (str_ends_with(rtrim($linha), ';')) {
                    $colunas = null;
                }
            }
        } finally {
            fclose($h);
        }
    }

    /** A tupla fecha (") ,|;" no fim) sem estar dentro de uma string? */
    private function completa(string $texto): bool
    {
        $dentro = false;
        $n      = strlen($texto);

        for ($i = 0; $i < $n; $i++) {
            $c = $texto[$i];
            if ($dentro) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === "'") {
                    if (($texto[$i + 1] ?? '') === "'") {
                        $i++;
                    } else {
                        $dentro = false;
                    }
                }
            } elseif ($c === "'") {
                $dentro = true;
            }
        }

        return ! $dentro;
    }

    /** @return list<string|int|float|null>|null */
    private function tupla(string $texto): ?array
    {
        if ($texto === '' || $texto[0] !== '(') {
            return null;
        }

        $valores = [];
        $n       = strlen($texto);
        $i       = 1;

        while ($i < $n) {
            while ($i < $n && ($texto[$i] === ' ' || $texto[$i] === "\t")) {
                $i++;
            }

            if ($texto[$i] === "'") {
                $i++;
                $v = '';
                while ($i < $n) {
                    $c = $texto[$i];
                    if ($c === '\\') {
                        $v .= $this->escape($texto[$i + 1] ?? '');
                        $i += 2;

                        continue;
                    }
                    if ($c === "'") {
                        if (($texto[$i + 1] ?? '') === "'") {
                            $v .= "'";
                            $i += 2;

                            continue;
                        }
                        $i++;
                        break;
                    }
                    $v .= $c;
                    $i++;
                }
                $valores[] = $v;
            } else {
                $ini = $i;
                while ($i < $n && $texto[$i] !== ',' && $texto[$i] !== ')') {
                    $i++;
                }
                $bruto = trim(substr($texto, $ini, $i - $ini));
                $valores[] = strcasecmp($bruto, 'NULL') === 0 ? null : (is_numeric($bruto) ? $bruto + 0 : $bruto);
            }

            while ($i < $n && $texto[$i] === ' ') {
                $i++;
            }
            if (($texto[$i] ?? ')') === ',') {
                $i++;

                continue;
            }

            break; // ')' final
        }

        return $valores;
    }

    private function escape(string $c): string
    {
        return match ($c) {
            'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1a",
            default => $c, // \\ \' \" \%
        };
    }
}
