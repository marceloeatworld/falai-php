<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Enums;

enum ModelSort: string
{
    case Relevant = 'relevant';
    case Recent = 'recent';
}
