<?php

namespace App\Enums;

enum DocumentDeliverySource: string
{
    case AiGenerated = 'ai_generated';
    case Custom = 'custom';
}
