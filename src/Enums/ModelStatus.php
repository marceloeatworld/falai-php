<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Enums;

enum ModelStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
}
