<?php
/**
 * IPP Interpreter
 * Class for handling frame access exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class FrameAccessException extends IPPException
{
    public function __construct(string $message = "ERROR - the memory frame does not exist")
    {
        parent::__construct($message, ReturnCode::FRAME_ACCESS_ERROR);
    }
}