<?php
declare(strict_types=1);

namespace App\QueryBuilder;

use Doctrine\Common\Lexer\AbstractLexer;

class Lexer extends AbstractLexer
{
    public const T_NONE = 0;
    public const T_PARAM_UNTYPED = 1;
    public const T_PARAM_INTEGER = 2;
    public const T_PARAM_FLOAT = 3;
    public const T_PARAM_ARRAY = 4;
    public const T_PARAM_IDENTIFIER = 5;
    public const T_COND_BEGIN = 6;
    public const T_COND_END = 7;

    public function isParam(): bool
    {
        $type = $this->token->type;

        return $type >= 1 && $type <= 5;
    }

    protected function getCatchablePatterns(): array
    {
        return [
            '\?[dfa#]',
            '[{}]',
            '[^?{]+',
        ];
    }

    protected function getNonCatchablePatterns(): array
    {
        return [];
    }

    /**
     * @throws \Exception
     */
    protected function getType(string &$value): int
    {
        if ($value[0] == '?') {
            if (strlen($value) == 1) {
                return self::T_PARAM_UNTYPED;
            }

            return match ($value[1]) {
                'd' => self::T_PARAM_INTEGER,
                'f' => self::T_PARAM_FLOAT,
                'a' => self::T_PARAM_ARRAY,
                '#' => self::T_PARAM_IDENTIFIER,
                default => throw new \Exception('Should not be default'),
            };
        }

        return match ($value[0]) {
            '{' => self::T_COND_BEGIN,
            '}' => self::T_COND_END,
            default => self::T_NONE,
        };
    }
}
