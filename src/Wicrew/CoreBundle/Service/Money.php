<?php


namespace App\Wicrew\CoreBundle\Service;


use App\Wicrew\SaleBundle\Entity\Tax;

class Money {
    /* @var int */
    private const DEFAULT_PRECISION = 2;
    /* @var int */
    private const PERCENTAGE_PRECISION = 4;

    /* @var string $internalValue */
    private $internalValue;

    /**
     * @param string $value
     */
    public function __construct(string $value = '0.00') {
        $this->internalValue = $value;
    }

    public function add(Money $other): Money {
        return new Money(bcadd($this->internalValue, $other->internalValue, self::DEFAULT_PRECISION));
    }

    public function addStr(string $other): Money {
        return new Money(bcadd($this->internalValue, $other, self::DEFAULT_PRECISION));
    }

    public function subtract(Money $other): Money {
        return new Money(bcsub($this->internalValue, $other->internalValue, self::DEFAULT_PRECISION));
    }

    public function subtractStr(string $other): Money {
        return new Money(bcsub($this->internalValue, $other, self::DEFAULT_PRECISION));
    }

    public function multiply(int $inter): Money {
        return new Money(bcmul($this->internalValue, (string)$inter, self::DEFAULT_PRECISION));
    }

    public function multiplyByTax(Tax $tax): Money {
        $percentage = bcmul($tax->getAmount(), '0.01', self::PERCENTAGE_PRECISION);

        $scaledValue = bcmul($this->internalValue, $percentage, self::DEFAULT_PRECISION);
        return new Money($scaledValue);
    }

    public function equals(Money $other): bool {
        return bccomp($this->internalValue, $other->internalValue, self::DEFAULT_PRECISION) === 0;
    }

    public function greaterThan(Money $other): bool {
        return $this->greaterThanStr($other->internalValue);
    }

    public function greaterThanStr(string $other): bool {
        return bccomp($this->internalValue, $other, self::DEFAULT_PRECISION) > 0;
    }

    public function greaterThanOrEqualStr(string $other): bool {
        return bccomp($this->internalValue, $other, self::DEFAULT_PRECISION) >= 0;
    }

    public function lessThan(Money $other): bool {
        return $this->lessThanStr($other->internalValue);
    }

    public function lessThanStr(string $other): bool {
        return bccomp($this->internalValue, $other, self::DEFAULT_PRECISION) < 0;
    }

    public function lessThanOrEqualStr(string $other): bool {
        return bccomp($this->internalValue, $other, self::DEFAULT_PRECISION) <= 0;
    }

    public function negate(): Money {
        return new Money(bcmul($this->internalValue, '-1.00', self::DEFAULT_PRECISION));
    }

    public function toCents(): int {
        $cents = bcmul($this->internalValue, '100', 0);
        return (int)$cents;
    }

    public static function fromCents(int $cents): Money {
        return new Money(bcmul($cents, '0.01', self::DEFAULT_PRECISION));
    }

    public function __toString(): string {
        return $this->internalValue;
    }
}