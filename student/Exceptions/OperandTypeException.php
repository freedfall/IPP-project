<?php
/**
 * IPP Interpreter
 * Class for handling Operand type exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class OperandTypeException extends IPPException
{
    public function __construct(string $message = "ERROR - wrong operand types")
    {
        parent::__construct($message, ReturnCode::OPERAND_TYPE_ERROR);
    }
}