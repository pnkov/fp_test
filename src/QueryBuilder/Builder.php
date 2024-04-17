<?php
declare(strict_types=1);

namespace App\QueryBuilder;

use App\QueryBuilder\Exception\UnexpectedBlockClosing;
use App\QueryBuilder\Exception\UnexpectedBlockOpening;
use mysqli;

/**
 * Replaces query parameters with the provided arguments
 */
class Builder
{
    /**
     * @var \App\QueryBuilder\Lexer
     */
    protected Lexer $lexer;

    /**
     * Accumulates parsed query
     *
     * @var string
     */
    protected string $queryAcc = '';

    /**
     * Accumulates parsed condition block
     *
     * @var string
     */
    protected string $condAcc = '';

    /**
     * Is in a condition block
     *
     * @var bool
     */
    protected bool $inCond = false;

    /**
     * Will the current condition block be omitted
     *
     * @var bool
     */
    protected bool $skipCond = false;

    protected ArgumentStringifier $arg;

    /**
     * @param \mysqli $mysqli
     * @param string  $query
     * @param array   $args
     */
    public function __construct(mysqli $mysqli, string $query, array $args)
    {
        $this->lexer = new Lexer();
        $this->lexer->setInput($query);
        $this->arg = new ArgumentStringifier($mysqli, $this->lexer, $args);
    }

    /**
     * @throws \App\QueryBuilder\Exception\UnexpectedBlockClosing
     * @throws \App\QueryBuilder\Exception\UnexpectedBlockOpening
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     * @throws \App\QueryBuilder\Exception\BadParamType
     */
    public function build(): string
    {
        if ($this->queryAcc != '') {
            return $this->queryAcc;
        }

        $this->lexer->moveNext();

        while (true) {
            if (!$this->lexer->lookahead) {
                break;
            }

            $this->lexer->moveNext();

            // add plain sql to output
            if ($this->lexer->token->isA(Lexer::T_NONE)) {
                $this->addOutput($this->lexer->token->value);
            }

            // replace parameter with argument value
            if ($this->lexer->isParam()) {
                $this->addOutput($this->getParamValue());
            }

            // prepare for condition block
            if ($this->lexer->token->isA(Lexer::T_COND_BEGIN)) {
                if ($this->inCond) {
                    throw new UnexpectedBlockOpening($this->lexer->token->position);
                }
                $this->setCond(true);
            }

            // add parsed condition block to final query if it is not skipped
            if ($this->lexer->token->isA(Lexer::T_COND_END)) {
                if (!$this->inCond) {
                    throw new UnexpectedBlockClosing($this->lexer->token->position);
                }

                if (!$this->skipCond) {
                    $this->queryAcc .= $this->condAcc;
                }

                $this->setCond(false);
            }
        }

        $this->arg->checkIsAllConsumed();

        return $this->queryAcc;
    }

    /**
     * Gets current argument
     *
     * @return string Stringified and escaped argument or empty string in case of \App\QueryBuilder\SkipArgument
     * @throws \App\QueryBuilder\Exception\ArgumentCountMismatch
     * @throws \App\QueryBuilder\Exception\BadParamType
     */
    protected function getParamValue(): string
    {
        $value = '';

        if ($this->arg->isSkip()) {
            $this->skipCond = true;
        } else {
            $value = $this->arg->stringifyCurrent();
        }

        $this->arg->next();

        return $value;
    }

    /**
     * Adds query part to query accumulator or condition accumulator
     *
     * @param string $str
     *
     * @return void
     */
    protected function addOutput(string $str): void
    {
        if (!$this->inCond) {
            $this->queryAcc .= $str;
        } else {
            $this->condAcc .= $str;
        }
    }

    /**
     * Sets condition flag and cleans up condition variables
     *
     * @param bool $inCond
     *
     * @return void
     */
    protected function setCond(bool $inCond): void
    {
        $this->inCond = $inCond;
        $this->skipCond = false;
        $this->condAcc = '';
    }
}
