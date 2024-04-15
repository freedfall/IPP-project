<?php
/**
 * IPP Interpreter
 * Class for handling parameter exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class ParameterException extends IPPException
{
    public function __construct(string $message = "ERROR - a missing script parameter or the use of a forbidden parameter combination")
    {
        parent::__construct($message, ReturnCode::PARAMETER_ERROR);
    }
}