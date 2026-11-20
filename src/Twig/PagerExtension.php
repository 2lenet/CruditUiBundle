<?php

declare(strict_types=1);

namespace Lle\CruditUiBundle\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Helper of the Pager: the parameters of a query as the fields of a GET form.
 */
class PagerExtension
{
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
