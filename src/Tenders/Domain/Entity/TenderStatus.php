<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Entity;

enum TenderStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Draft = 'draft';
}
