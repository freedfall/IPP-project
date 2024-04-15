<?php
/**
 * IPP Interpreter
 * Class for handling source structure exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class SourceStructureException extends IPPException
{
    public function __construct(string $message = "unexpected XML structure (e.g. element for argument outside element for instruction, instructions with duplicate order or negative order)")
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE);
    }
}