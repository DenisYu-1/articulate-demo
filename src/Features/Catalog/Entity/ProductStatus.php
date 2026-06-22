<?php

namespace App\Features\Catalog\Entity;

enum ProductStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Discontinued = 'discontinued';
}
