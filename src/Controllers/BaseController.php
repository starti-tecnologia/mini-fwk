<?php

namespace Mini\Controllers;

use Mini\Entity\Entity;
use Mini\Helpers\Request;

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
        $this->onBeforeValidate();
        $this->getValidator()->validate($data);
    }

    /**
     * @throws \Mini\Validation\ValidationException
     * 
     * @param Entity $entity Entity to be used with validation
     * @param array $extraDefinition
     */
    public function validateEntity(Entity $entity, $extraDefinition = [])
    {
        $this->onBeforeValidate();
        $this->getValidator()->validateEntity($entity, $extraDefinition);
    }

    /**
     * Merge and validate entities rules by keys, example:
     *
     * $validator->validateEntities([
     *    '*' => new Retailer,
     *    'owner' => new User
     * ]);
     *
     * @param array $entities
     * @throws \Mini\Exceptions\ValidationException
     */
    public function validateEntities(array $entities, $extraDefinition = [])
    {
        $this->onBeforeValidate();
        $this->getValidator()->validateEntities($entities, $extraDefinition);
    }

    public function getValidator()
    {
        if (! $this->validator) {
            $this->validator = app()->get('Mini\Validation\Validator');
        }

        return $this->validator;
    }
}
