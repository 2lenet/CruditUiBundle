<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Component\ButtonVariant;
use PHPUnit\Framework\TestCase;

/**
 * ButtonVariant, the styles, the comment and the default of Button and the documentation list the same variants (the
 * design kit takes them from ButtonVariant).
 */
class ButtonVariantTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../../..';

    public function testEachVariantHasItsStyle(): void
    {
        $scss = (string) file_get_contents(self::ROOT . '/assets/styles/components/_button.scss');

        foreach (ButtonVariant::values() as $variant) {
            self::assertMatchesRegularExpression(
                '/^    &--' . preg_quote($variant, '/') . ' \{/m',
                $scss,
                "No &--$variant rule in components/_button.scss",
            );
        }
    }

    public function testTheCommentOfButtonListsThem(): void
    {
        self::assertSame(1, preg_match('/variant \(([^;]+);/', self::button(), $matches), 'No "variant (…;" in the comment of Button');
        self::assertSame(ButtonVariant::values(), self::split($matches[1]), 'The comment of Button and ButtonVariant differ');
    }

    public function testTheDefaultOfButtonIsAVariant(): void
    {
        self::assertSame(1, preg_match("/\{% props variant = '([^']+)'/", self::button(), $matches), 'No default variant in the props of Button');
        self::assertNotNull(ButtonVariant::tryFrom($matches[1]), "The default variant of Button, $matches[1], is not in ButtonVariant");
    }

    public function testTheDocumentationListsThem(): void
    {
        $markdown = (string) file_get_contents(self::ROOT . '/docs/components.md');

        // The first `variant` row of the props is the one of Button.
        self::assertSame(1, preg_match('/^\| `variant` \| ([^|]+) \|/m', $markdown, $matches), 'No `variant` row in docs/components.md');
        self::assertSame(
            ButtonVariant::values(),
            array_map(static fn (string $variant): string => trim($variant, '`'), self::split($matches[1])),
            'The `variant` row of Button in docs/components.md and ButtonVariant differ',
        );
    }

    private static function button(): string
    {
        return (string) file_get_contents(self::ROOT . '/templates/components/Button.html.twig');
    }

    /**
     * A list cut over several lines of a comment ("danger-outline,\n # link") gives the same items.
     *
     * @return list<string>
     */
    private static function split(string $list): array
    {
        $items = preg_split('/,\s*(?:#\s*)?/', trim($list));

        return $items === false ? [] : $items;
    }
}
