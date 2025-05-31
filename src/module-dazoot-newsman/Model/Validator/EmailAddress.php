<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Model\Validator;

use Laminas\Validator\Exception\ExceptionInterface;

/**
 * Email address validator
 */
class EmailAddress
{
    /**
     * @param string $email
     * @return false
     */
    public function isValid($email)
    {
        try {
            $validator = new \Laminas\Validator\EmailAddress();
            return !empty($email) && $validator->isValid($email);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }
}
