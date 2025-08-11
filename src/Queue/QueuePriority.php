<?php

namespace MarceloEatWorld\FalAI\Queue;

enum QueuePriority: string
{
    case High = 'high';
    case Normal = 'normal'; 
    case Low = 'low';
}