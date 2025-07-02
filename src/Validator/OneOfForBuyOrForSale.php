<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class OneOfForBuyOrForSale extends Constraint
{
    public string $message = 'Veuillez préciser si c\'est pour les ventes, les achats ou les 2';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
