<?php

namespace App\Validator;

use App\Entity\Market;
use App\Validator\OneOfForBuyOrForSale;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OneOfForBuyOrForSaleValidator extends ConstraintValidator
{
    public function validate($market, Constraint $constraint)
    {
        if (!$constraint instanceof OneOfForBuyOrForSale) {
            throw new UnexpectedTypeException($constraint, OneOfForBuyOrForSale::class);
        }

        if (!$market instanceof Market) {
            return;
        }

        if (!$market->isForBuy() && !$market->isForSale()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
