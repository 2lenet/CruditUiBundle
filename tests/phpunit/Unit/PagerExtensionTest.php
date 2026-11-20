<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\PagerExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PagerExtensionTest extends TestCase
{
    public function testAQueryBecomesTheFieldsOfAForm(): void
    {
        $pairs = (new PagerExtension())->queryPairs([
            'q' => 'a b',
            'filter' => ['status' => ['late', 'open'], 'city' => 'Lyon'],
            'archived' => false,
            'urgent' => true,
            'none' => null,
            'number' => 3,
        ]);

        self::assertSame([
            ['name' => 'q', 'value' => 'a b'],
            ['name' => 'filter[status][0]', 'value' => 'late'],
            ['name' => 'filter[status][1]', 'value' => 'open'],
            ['name' => 'filter[city]', 'value' => 'Lyon'],
            ['name' => 'archived', 'value' => '0'],
            ['name' => 'urgent', 'value' => '1'],
            ['name' => 'number', 'value' => '3'],
        ], $pairs);
    }

    public function testNothingToSend(): void
    {
        self::assertSame([], (new PagerExtension())->queryPairs([]));
    }

    /**
     * @return iterable<string, array{int, int, list<int|null>}>
     */
    public static function windows(): iterable
    {
        yield 'one page' => [1, 1, [1]];
        yield 'two pages' => [2, 2, [1, 2]];
        yield 'five pages, in the middle' => [3, 5, [1, 2, 3, 4, 5]];
        yield 'first of twelve' => [1, 12, [1, 2, null, 12]];
        yield 'second of twelve' => [2, 12, [1, 2, 3, null, 12]];
        yield 'a gap of one page shows its number' => [4, 12, [1, 2, 3, 4, 5, null, 12]];
        yield 'the middle' => [6, 12, [1, null, 5, 6, 7, null, 12]];
        yield 'before the last' => [11, 12, [1, null, 10, 11, 12]];
        yield 'the same at the end' => [9, 12, [1, null, 8, 9, 10, 11, 12]];
        yield 'last of twelve' => [12, 12, [1, null, 11, 12]];
        yield 'a big list' => [10000, 20000, [1, null, 9999, 10000, 10001, null, 20000]];
    }

    /**
     * @param list<int|null> $expected
     */
    #[DataProvider('windows')]
    public function testTheNumbersToShow(int $current, int $count, array $expected): void
    {
        self::assertSame($expected, (new PagerExtension())->pages($current, $count));
    }

    public function testTheChoicesHoldTheRowsPerPageInUse(): void
    {
        $extension = new PagerExtension();

        self::assertSame([10, 25, 30, 50], $extension->choices([50, 10, 30, '30'], 25));
        self::assertSame([10, 30], $extension->choices([10, 30], 30), 'no double');
        self::assertSame([], $extension->choices([], 30), 'no choice, no selector');
        self::assertSame([10, 30], $extension->choices([0, 10, 30], 30), 'a choice of 0 is left out');
    }
}
