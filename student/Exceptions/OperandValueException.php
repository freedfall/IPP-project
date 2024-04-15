<?php
/**
 * IPP Interpreter
 * Class for handling Operand value exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class OperandValueException extends IPPException
{
    public function __construct(string $message = "ERROR -  wrong operand value")
    {
        parent::__construct($message, ReturnCode::OPERAND_VALUE_ERROR);
    }
}