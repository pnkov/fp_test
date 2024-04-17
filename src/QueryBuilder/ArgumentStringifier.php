<?php
declare(strict_types=1);

namespace App\QueryBuilder;

use App\QueryBuilder\Exception\ArgumentCountMismatch;
use App\QueryBuilder\Exception\BadParamType;
use Exception;
use mysqli;

/**
 * Holds argument array and prepares its values to be safely used in a query
 */
class ArgumentStringifier
{
    /**
     * Argument index
     *
     * @var int
     */
    protected int $argi = 0;

    /**
     * @param \mysqli                 $mysqli
     * @param \App\QueryBuilder\Lexer $lexer
     * @param array                   $args
     */
    public function __construct(
        protected readonly mysqli $mysqli,
        protected readonly Lexer  $lexer,
        protected array           $args,
    )
    {
    }

    /**
     * Checks if current argument is skip value
     *
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     */
    public function isSkip(): bool
    {
        $v = $this->current();

        return is_object($v) && is_a($v, SkipArgument::class);
    }

    /**
     * Gets dummy skip class
     *
     * @return \App\QueryBuilder\SkipArgument
     */
    public static function GetSkipValue(): SkipArgument
    {
        return new SkipArgument();
    }

    /**
     * Prepares current argument value based on lexer token type
     *
     * @throws \App\QueryBuilder\Exception\BadParamType
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     */
    public function stringifyCurrent(): string
    {
        $v = $this->current();
        $type = $this->lexer->token->type;

        return $this->stringify($type, $v);
    }

    /**
     * Advances argument index
     *
     * @return void
     */
    public function next(): void
    {
        $this->argi++;
    }

    /**
     * Checks if all arguments were used in query
     *
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     */
    public function checkIsAllConsumed(): void
    {
        if ($this->argi != count($this->args)) {
            throw new ArgumentCountMismatch(count($this->args), $this->argi);
        }
    }

    /**
     * Returns argument as is
     *
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     */
    protected function current()
    {
        if (!key_exists($this->argi, $this->args)) {
            throw new ArgumentCountMismatch(count($this->args), $this->argi);
        }

        return $this->args[$this->argi];
    }

    /**
     * Prepares argument value based on lexer token type
     *
     * @param int|null $type
     * @param          $v
     *
     * @return string
     * @throws \App\QueryBuilder\Exception\BadParamType
     * @throws \Exception should not be thrown
     */
    protected function stringify(?int $type, $v): string
    {
        return match ($type) {
            Lexer::T_PARAM_UNTYPED => $this->stringifyUntyped($v),
            Lexer::T_PARAM_INTEGER => $v !== null ? (string) (int) $v : 'NULL',
            Lexer::T_PARAM_FLOAT => $v !== null ? (string) (float) $v : 'NULL',
            Lexer::T_PARAM_ARRAY => $this->stringifyArray($v),
            Lexer::T_PARAM_IDENTIFIER => $this->stringifyIdentifier($v),
            default => throw new Exception("Can not stringify lexer token $type"),
        };
    }

    /**
     * Processes argument based on its own type
     *
     * @throws \App\QueryBuilder\Exception\BadParamType
     */
    protected function stringifyUntyped($v): string
    {
        $type = gettype($v);

        return match ($type) {
            'string' => "'" . $this->mysqli->escape_string($v) . "'",
            'integer', 'double' => (string) $v,
            'boolean' => (string) (int) $v,
            'NULL' => 'NULL',
            default => throw new BadParamType($type, $this->argi),
        };
    }

    /**
     * Joins indexed array to sequence of values or associative array to `key = value` sequence
     * Also escapes and quotes identifiers and values respectively
     *
     * @param array $v
     *
     * @return string
     * @throws \App\QueryBuilder\Exception\BadParamType
     */
    protected function stringifyArray(array $v): string
    {
        if (array_is_list($v)) {
            return join(', ', array_map([$this, 'stringifyUntyped'], $v));
        } else {
            return join(', ', array_map(
                fn($k, $v) => $this->stringifyIdentifier($k) . " = " . $this->stringifyUntyped($v),
                array_keys($v), array_values($v),
            ));
        }
    }

    /**
     * Escape and quote identifier
     *
     * @param $v
     *
     * @return string
     */
    protected function stringifyIdentifier($v): string
    {
        if (!is_array($v)) {
            $v = [$v];
        }

        return join(', ', array_map(
            fn($v) => '`' . $this->mysqli->escape_string(str_replace('`', '``', $v)) . '`',
            $v,
        ));
    }
}
