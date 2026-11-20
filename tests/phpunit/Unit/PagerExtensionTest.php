<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Tests\Unit;

use Lle\CruditUiBundle\Twig\PagerExtension;
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
}
