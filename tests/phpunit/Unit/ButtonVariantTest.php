<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Component\ButtonVariant;
use PHPUnit\Framework\TestCase;

/**
 * ButtonVariant, the comment of Button and the styles list the same variants.
 */
class ButtonVariantTest extends TestCase
{
    public function testEachVariantHasItsStyle(): void
    {
        $scss = (string) file_get_contents(__DIR__ . '/../../../assets/styles/components/_button.scss');

        foreach (ButtonVariant::values() as $variant) {
            self::assertMatchesRegularExpression('/^    &--' . preg_quote($variant, '/') . ' \{/m', $scss, $variant);
        }
    }

    public function testTheCommentOfButtonListsThem(): void
    {
        $twig = (string) file_get_contents(__DIR__ . '/../../../templates/components/Button.html.twig');

        self::assertSame(1, preg_match('/variant \(([^;]+);/', $twig, $matches));
        self::assertSame(ButtonVariant::values(), explode(', ', $matches[1]));
    }
}
