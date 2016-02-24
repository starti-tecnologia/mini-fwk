<?php

namespace Mini\Controllers;

use Mini\Entity\Entity;

class BaseController
{
    /**
     * @var \Mini\Validation\Validator
     */
    private $validator;

    private function onBeforeValidate()
    {
        $this->getValidator()->setData(Request::instance()->get('data'));
    }

    /**
     * @throws \Mini\Validation\ValidationException
     */
    public function validate(array $data)
    {
        $this->getValidator()->validate($data);
    }

    /**
     * @throws \Mini\Validation\ValidationException
     */
    public function validateEntity(Entity $entity)
    {
        $this->getValidator()->validateEntity($data);
    }

    public function getValidator()
    {
        if (! $this->validator) {
            $this->validator = app()->get('Mini\Validation\Validator');
        }

        return $this->validator;
    }
}
