<?php
/**
 * IPP Interpreter
 * Class for handling semantic exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class SemanticException extends IPPException
{
    public function __construct(string $message = "Error during semantic checks of input code in IPPcode24")
    {
        parent::__construct($message, ReturnCode::SEMANTIC_ERROR);
    }
}