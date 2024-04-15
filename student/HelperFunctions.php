<?php
/**
 * IPP Interpreter
 * Class for using helper methods
 * @author Timur Kininbayev (xkinin00)
 * 
 */

namespace IPP\Student;
use IPP\Student\Exceptions\ValueException;
use IPP\Student\Exceptions\SemanticException;
use IPP\Student\Exceptions\ParameterException;
use IPP\Student\Exceptions\OperandTypeException;
use IPP\Student\Exceptions\FrameAccessException;
use IPP\Student\Exceptions\OperandValueException;
use IPP\Student\Exceptions\VariableAccessException;
use IPP\Student\Exceptions\SourceStructureException;
use IPP\Student\Exceptions\StringOperationException;

use IPP\Core\ReturnCode;
use IPP\Core\Exception\XMLException;
use IPP\Core\Exception\InputFileException;
use IPP\Core\Exception\OutputFileException;
use IPP\Core\Exception\IntegrationException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Core\Exception\NotImplementedException;


class HelperFunctions {

    public static function handleException(int $errorCode): void
    {
        switch ($errorCode){
            case ReturnCode::OK:
                break;
            case ReturnCode::PARAMETER_ERROR:
                throw new ParameterException();
            case ReturnCode::INPUT_FILE_ERROR:
                throw new InputFileException();
            case ReturnCode::OUTPUT_FILE_ERROR:
                throw new OutputFileException();
            case ReturnCode::INVALID_XML_ERROR:
                throw new XMLException();
            case ReturnCode::INVALID_SOURCE_STRUCTURE:
                throw new SourceStructureException();
            case ReturnCode::SEMANTIC_ERROR:
                throw new SemanticException();
            case ReturnCode::OPERAND_TYPE_ERROR:
                throw new OperandTypeException();
            case ReturnCode::VARIABLE_ACCESS_ERROR:
                throw new VariableAccessException();
            case ReturnCode::FRAME_ACCESS_ERROR:
                throw new FrameAccessException();
            case ReturnCode::VALUE_ERROR:
                throw new ValueException();
            case ReturnCode::OPERAND_VALUE_ERROR:
                throw new OperandValueException();
            case ReturnCode::STRING_OPERATION_ERROR:
                throw new StringOperationException();
            case ReturnCode::INTEGRATION_ERROR:
                throw new IntegrationException();
            case ReturnCode::INTERNAL_ERROR:
                throw new InternalErrorException();
            default:
                throw new NotImplementedException();
        }
    }

    /**
     * Function for checking data type of variable
     * @param mixed $var - variable to check
     * @return string - data type of variable
     * @throws \Exception - if the data type is not supported
     */
    public static function getDataType(mixed $var): string
    {
        if (is_int($var)) {
            return "int";
        } elseif (is_float($var)) {
            return "float";
        } elseif (is_bool($var)) {
            return "bool";
        } elseif ($var === "nil@nil") {
            return "nil";
        } elseif (is_string($var)) {
            return "string";
        } else {
            throw new \Exception("Unsupported data type");
        }
    }

    /**
     * Function for checking data type of variable
     * @param array<mixed> $args
     * @param int $count
     * @throws \Exception - if the data type is not supported
     */
    public static function checkArgs(array $args, int $count): void
    {
        if (count($args) != $count) {
            self::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
    }

    /**
     * Function for checking if index is in string range
     * @param string $string 
     * @param int $index
     * @throws \Exception - if the data type is not supported
     */
    public static function checkIndex(string $string, int $index): void
    {
        if ($index < 0 || $index >= mb_strlen($string, "UTF-8")) {
            self::handleException(ReturnCode::STRING_OPERATION_ERROR);
            return;
        }
    }

    /**
     * Function for checking if index is in string range
     * @param string $str
     * @return string - decoded string 
     * @throws \Exception - if the data type is not supported
     */
    public static function decodeEscapedCharacters($str) {
        return preg_replace_callback('/\\\\(\d{3})/', function($matches) {
            return chr(intval($matches[1]));
        }, $str);
    }
}