<?php

namespace App\Features\Catalog\Type;

use App\Features\Catalog\Entity\ProductStatus;
use Articulate\Utils\TypeConverterInterface;

final class ProductStatusConverter implements TypeConverterInterface
{
    public function convertToPHP(mixed $value): ?ProductStatus
    {
        if ($value === null || $value instanceof ProductStatus) {
            return $value;
        }

        return ProductStatus::from((string) $value);
    }

    public function convertToDatabase(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ProductStatus) {
            return $value->value;
        }

        return (string) $value;
    }
}
