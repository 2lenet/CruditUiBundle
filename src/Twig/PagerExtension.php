<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Helpers of the Pager: the numbers of the pages to show, the choices of the rows per page, and the parameters of a
 * query as the fields of a GET form.
 */
class PagerExtension
{
    /**
     * The numbers shown between "previous" and "next": the first page, the last one and the neighbours of the current
     * page, `null` for a gap ("…"). A gap of one page shows its number (1 2 3 4 5 … 12, not 1 … 3 4 5 … 12 when 2 is
     * the only one missing).
     *
     * @return list<int|null>
     */
    #[AsTwigFunction('ui_pager_pages')]
    public function pages(int $current, int $count): array
    {
        $shown = array_unique([1, $count, ...range(max($current - 1, 1), min($current + 1, $count))]);
        sort($shown);

        $pages = [];
        $previous = 0;
        foreach ($shown as $page) {
            if ($page < 1 || $page > $count) {
                continue;
            }
            if ($page - $previous === 2) {
                $pages[] = $previous + 1;
            } elseif ($page - $previous > 2) {
                $pages[] = null;
            }
            $pages[] = $page;
            $previous = $page;
        }

        return $pages;
    }

    /**
     * The choices of the rows per page with the number in use, without doubles, in order. No choice (an empty list)
     * stays empty: no selector.
     *
     * @param array<array-key, mixed> $choices
     *
     * @return list<int>
     */
    #[AsTwigFunction('ui_pager_choices')]
    public function choices(array $choices, int $perPage): array
    {
        if ([] === $choices) {
            return [];
        }

        $all = array_unique(array_filter([...array_map('intval', array_values($choices)), $perPage], static fn (int $choice): bool => $choice > 0));
        sort($all);

        return $all;
    }

    /**
     * The scalar values of a query, one `{name, value}` per field: `['a' => ['b' => 'c']]` gives `a[b]` = `c`. A value
     * that is not a scalar (an object, null) is left out, a boolean is sent as `1` or `0`.
     *
     * @param array<array-key, mixed> $query
     *
     * @return list<array{name: string, value: string}>
     */
    #[AsTwigFunction('ui_query_pairs')]
    public function queryPairs(array $query, ?string $prefix = null): array
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            $name = null === $prefix ? (string) $key : $prefix . '[' . $key . ']';
            if (\is_array($value)) {
                array_push($pairs, ...$this->queryPairs($value, $name));
            } elseif (\is_scalar($value)) {
                $pairs[] = ['name' => $name, 'value' => \is_bool($value) ? ($value ? '1' : '0') : (string) $value];
            }
        }

        return $pairs;
    }
}
