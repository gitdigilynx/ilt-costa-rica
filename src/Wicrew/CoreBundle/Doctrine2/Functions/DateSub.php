<?php

namespace App\Wicrew\CoreBundle\Doctrine2\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DateSub
 *
 * DateSubFunction ::=
 *     "DATE_SUB" "(" ArithmeticPrimary ", INTERVAL" ArithmeticPrimary Identifier ")"
 */
class DateSub extends FunctionNode {

    /**
     * First date expression
     *
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    private $firstDateExpression;

    /**
     * Interval expression
     *
     * @var \Doctrine\ORM\Query\AST\Literal
     */
    private $intervalExpression;

    /**
     * Unit
     *
     * @var string
     */
    private $unit;

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->setFirstDateExpression($parser->ArithmeticPrimary());

        $parser->match(Lexer::T_COMMA);
        $parser->match(Lexer::T_IDENTIFIER);

        $this->setIntervalExpression($parser->ArithmeticPrimary());

        $parser->match(Lexer::T_IDENTIFIER);

        $lexer = $parser->getLexer();
        $this->setUnit($lexer->token['value']);

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker) {
        return 'DATE_SUB(' . $this->getFirstDateExpression()->dispatch($sqlWalker) . ', INTERVAL ' . $this->getIntervalExpression()->dispatch($sqlWalker) . ' ' . $this->getUnit() . ')';
    }

    /**
     * Get first date expression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function getFirstDateExpression() {
        return $this->firstDateExpression;
    }

    /**
     * Set first date expression
     *
     * @param \Doctrine\ORM\Query\AST\PathExpression $firstDateExpression
     *
     * @return DateSub
     */
    public function setFirstDateExpression($firstDateExpression): DateSub {
        $this->firstDateExpression = $firstDateExpression;
        return $this;
    }

    /**
     * Get interval expression
     *
     * @return \Doctrine\ORM\Query\AST\Literal
     */
    public function getIntervalExpression() {
        return $this->intervalExpression;
    }

    /**
     * Set interval expression
     *
     * @param \Doctrine\ORM\Query\AST\Literal $intervalExpression
     *
     * @return DateSub
     */
    public function setIntervalExpression($intervalExpression): DateSub {
        $this->intervalExpression = $intervalExpression;
        return $this;
    }

    /**
     * Get unit
     *
     * @return string
     */
    public function getUnit() {
        return $this->unit;
    }

    /**
     * Set unit
     *
     * @param string
     *
     * @return DateSub
     */
    public function setUnit($unit): DateSub {
        $this->unit = $unit;
        return $this;
    }

}
