<?php

namespace App\Twig;

use App\Enum\CategoryEnum;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'categories' => CategoryEnum::cases(),
        ];
    }
}
