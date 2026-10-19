<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\LleCruditUiBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Icon;

/**
 * The bundle copies the rule of `Icon::isValidName()`, internal to UX Icons: a new version that changes it shows here.
 */
class IconNameTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function names(): iterable
    {
        foreach (['tabler:trash-x', 'logo', 'flags:fr', 'a:b:c', 'fa7-solid:arrow-right', 'Logo', 'fa-solid fa-trash', 'a::b', '-a', 'a-', 'a--b', ':a', 'a:', 'é'] as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('names')]
    public function testRuleOfUxIcons(string $name): void
    {
        self::assertSame(Icon::isValidName($name), 1 === preg_match(LleCruditUiBundle::ICON_NAME, $name));
    }
}
