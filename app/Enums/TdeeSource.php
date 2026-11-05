<?php

namespace App\Enums;

/** Where the current TDEE comes from. */
enum TdeeSource: string
{
    /** Not enough data: no weight and no manual estimate. */
    case None = 'none';
    /** Manual start_tdee or Mifflin-St Jeor (first warmup days, or no reliable window yet). */
    case Start = 'start';
    /** Calculated from logged intake and the weight trend. */
    case Fact = 'fact';
}
