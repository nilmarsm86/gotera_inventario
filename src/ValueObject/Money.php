<?php

namespace App\ValueObject;

final readonly class Money
{
    private int $amount; // en centavos
    private string $currency;

    public function __construct(int $amount, string $currency = 'CUP')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Monto inválido');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getValue(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->getValue(), $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        if ($this->amount < $other->getValue()) {
            throw new \RuntimeException('Resultado negativo');
        }

        return new self($this->amount - $other->getValue(), $this->currency);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException('Monedas distintas');
        }
    }

    public function multiply(Quantity|int $factor): self
    {
        if ($factor instanceof Quantity) {
            $factor = $factor->getValue();
        }

        if ($factor <= 0) {
            throw new \InvalidArgumentException('Factor inválido');
        }

        return new self($this->amount * $factor, $this->currency);
    }

    public function isSame(Money $other): bool
    {
        try {
            $this->assertSameCurrency($other);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }

        return $this->amount === $other->getValue();
    }

    public function priceFormat(): string
    {
        return number_format($this->amount, 2);
    }
}
