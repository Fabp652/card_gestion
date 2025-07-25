<?php

namespace App\Service;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validate
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function validate(mixed $value): array
    {
        $violations = $this->validator->validate($value);
        $messages = [];
        if ($violations->count() > 0) {
            if ($violations->count() > 0) {
                foreach ($violations as $violation) {
                    $messages[$violation->getPropertyPath()] = $violation->getMessage();
                }
            }
        }
        return $messages;
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    public function getFormErrors(FormInterface $form): array
    {
        $messages = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()->getName();
            $messages[$field] = $error->getMessage();
        }

        return $messages;
    }
}
