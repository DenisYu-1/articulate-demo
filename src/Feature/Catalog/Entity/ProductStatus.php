<?php

namespace App\Feature\Catalog\Entity;

enum ProductStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Discontinued = 'discontinued';
}
