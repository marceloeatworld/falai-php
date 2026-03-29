<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Enums;

enum Status: string
{
    case InQueue = 'IN_QUEUE';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
}
