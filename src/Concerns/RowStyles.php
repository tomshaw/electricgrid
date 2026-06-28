<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

/**
 * Fluent configuration for row appearance (hover, zebra stripes) and the inline
 * CSS custom properties emitted on the table wrapper.
 */
trait RowStyles
{
    public ?string $rowHoverColor = null;

    public ?string $rowHoverColorDark = null;

    public ?string $rowStripeOdd = null;

    public ?string $rowStripeEven = null;

    public ?string $rowStripeOddDark = null;

    public ?string $rowStripeEvenDark = null;

    public function rowHover(?string $light = null, ?string $dark = null): static
    {
        $this->rowHoverColor = $light;
        $this->rowHoverColorDark = $dark;

        return $this;
    }

    public function rowStripes(?string $odd = null, ?string $even = null, ?string $oddDark = null, ?string $evenDark = null): static
    {
        $this->rowStripeOdd = $odd;
        $this->rowStripeEven = $even;
        $this->rowStripeOddDark = $oddDark;
        $this->rowStripeEvenDark = $evenDark;

        return $this;
    }

    public function wrapperStyle(): string
    {
        $vars = [];

        $properties = [
            '--eg-row-hover' => $this->rowHoverColor,
            '--eg-row-hover-dark' => $this->rowHoverColorDark,
            '--eg-row-odd' => $this->rowStripeOdd,
            '--eg-row-even' => $this->rowStripeEven,
            '--eg-row-odd-dark' => $this->rowStripeOddDark,
            '--eg-row-even-dark' => $this->rowStripeEvenDark,
        ];

        foreach ($properties as $name => $value) {
            $color = $this->sanitizeHexColor($value);
            if ($color !== null) {
                $vars[] = "{$name}: {$color}";
            }
        }

        return implode('; ', $vars);
    }

    protected function sanitizeHexColor(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1 ? $color : null;
    }
}
