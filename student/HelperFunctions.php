<?php
/**
 * IPP Interpreter
 * Class for handling error codes
 * @author Timur Kininbayev (xkinin00)
 * 
 */

namespace IPP\Student;
use IPP\Core\ReturnCode;

class HelperFunctions {

    public static function handleException(int $errorCode): void
    {
        switch ($errorCode){
            case ReturnCode::OK:
                break;
            case ReturnCode::PARAMETER_ERROR:
                fwrite(STDERR, "ERROR - a missing script parameter or the use of a forbidden parameter combination;\n");
                exit(ReturnCode::PARAMETER_ERROR);
            case ReturnCode::INPUT_FILE_ERROR:
                fwrite(STDERR, "ERROR - error when opening input files\n");
                exit(ReturnCode::INPUT_FILE_ERROR);
            case ReturnCode::OUTPUT_FILE_ERROR:
                fwrite(STDERR, "ERROR - error when opening output files for writing\n");
                exit(ReturnCode::OUTPUT_FILE_ERROR);
            case ReturnCode::INVALID_XML_ERROR:
                fwrite(STDERR, "ERROR - incorrect XML format in the input file\n");
                exit(ReturnCode::INVALID_XML_ERROR);
            case ReturnCode::INVALID_SOURCE_STRUCTURE:
                fwrite(STDERR, "unexpected XML structure (e.g. element for argument outside element for instruction, instructions with duplicate order or negative order)\n");
                exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
            case ReturnCode::SEMANTIC_ERROR:
                fwrite(STDERR, "ERROR - error during semantic checks of input code in IPPcode24\n");
                exit(ReturnCode::SEMANTIC_ERROR);
            case ReturnCode::OPERAND_TYPE_ERROR:
                fwrite(STDERR, "ERROR - wrong operand types\n");
                exit(ReturnCode::OPERAND_TYPE_ERROR);
            case ReturnCode::VARIABLE_ACCESS_ERROR:
                fwrite(STDERR, "ERROR - access to a non-existing variable (memory frame exists)\n");
                exit(ReturnCode::VARIABLE_ACCESS_ERROR);
            case ReturnCode::FRAME_ACCESS_ERROR:
                fwrite(STDERR, "ERROR - the memory frame does not exist\n");
                exit(ReturnCode::FRAME_ACCESS_ERROR);
            case ReturnCode::VALUE_ERROR:
                fwrite(STDERR, "ERROR - missing value\n");
                exit(ReturnCode::VALUE_ERROR);
            case ReturnCode::OPERAND_VALUE_ERROR:
                fwrite(STDERR, "ERROR -  wrong operand value\n");
                exit(ReturnCode::OPERAND_VALUE_ERROR);
            case ReturnCode::STRING_OPERATION_ERROR:
                fwrite(STDERR, "ERRROR - incorrect work with the string\n");
                exit(ReturnCode::STRING_OPERATION_ERROR);
            case ReturnCode::INTEGRATION_ERROR:
                fwrite(STDERR, "ERROR - integration error\n");
                exit(ReturnCode::INTEGRATION_ERROR);
            case ReturnCode::INTERNAL_ERROR:
                fwrite(STDERR, "ERROR - internal error\n");
                exit(ReturnCode::INTERNAL_ERROR);
            default:
                fwrite(STDERR, "ERROR - unknown error\n");
                exit(ReturnCode::INTERNAL_ERROR);
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
        } elseif (is_string($var)) {
            return "string";
        } elseif (is_null($var)) {
            return "nil";
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
            self::handleException(ReturnCode::SEMANTIC_ERROR);
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
}