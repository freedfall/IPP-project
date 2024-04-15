<?php
/**
 * IPP Interpreter
 * Class for handling variable access exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class VariableAccessException extends IPPException
{
    public function __construct(string $message = "ERROR - access to a non-existing variable (memory frame exists)")
    {
        parent::__construct($message, ReturnCode::VARIABLE_ACCESS_ERROR);
    }
}