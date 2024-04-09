<?php
/**
 * IPP Interpreter
 * Class for processing instructions
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use DOMElement;
use IPP\Student\ErrorHandler;
use IPP\Core\ReturnCode;
use IPP\Core\FileInputReader;
use IPP\Core\Interface\InputReader;
use IPP\Student\HelperFunctions;

/**
 * Class InstructionProcessor
 * 
 * Processes instructions
 */
class InstructionProcessor
{
    /**
     * @var array<string,mixed> Global frame storage, where key is variable name and value is variable value
     */
    protected array $globalFrame = [];

    /**
     * @var array<string,mixed> Stack of frame indices for managing scope and variables (LF)
     */
    protected array $frameStack = [];

    /**
     * @var array<string,int> Call stack for managing function calls and returns
     */
    protected array $callStack = [];

    /**
     * @var array<string,int> Associative array where keys are label names and values are instruction indices
     */
    protected array $labels = [];

    /**
     * @var array<mixed>|null Temporary frame, can be null if not currently defined
     */
    protected ?array $tempFrame = null;
    /**
     * @var array<mixed> Associative array where keys are label names and values are instruction indices
     */
    protected array $dataStack = [];

    

    /**
     * @var InputReader Input reader
     */
    protected InputReader $inputReader;

    public int $instructionIndex = 0;
    public bool $indexModified = false;


    public function __construct(InputReader $inputReader){
        $this->inputReader = $inputReader;
    }
    /**
     * Processes the instruction.
     *
     * @param array{opcode: string, args: array<mixed>} $instruction Instruction to process, where:
     *        - 'opcode' is the operation code (string) of the instruction,
     *        - 'args' is an array of arguments for the instruction. The type of each argument can vary, hence 'mixed'.
     * @return string|null Result of the instruction or null if the instruction does not produce output.
     * @throws \Exception If the instruction is unknown or an error occurs.
     */
    public function processInstruction(array $instruction): ?string
    {
        switch (strtoupper($instruction['opcode'])) {
            case 'MOVE':
                return $this->handleMove($instruction['args']);
            case 'CREATEFRAME':
                return $this->handleCreateFrame();
            case 'PUSHFRAME':
                return $this->handlePushFrame();
            case 'POPFRAME':
                return $this->handlePopFrame();
            case 'DEFVAR':
                return $this->handleDefvar($instruction['args']);
            case 'CALL':
                return $this->handleCall($instruction['args']);
            case 'RETURN':
                return $this->handleReturn();
            case 'LABEL':
                return $this->handleLabel($instruction['args']);
            case 'PUSHS':
                return $this->handlePushs($instruction['args']);
            case 'POPS':
                return $this->handlePops($instruction['args']);
            case 'ADD':
                return $this->handleAdd($instruction['args']);
            case 'SUB':
                return $this->handleSub($instruction['args']);
            case 'MUL':
                return $this->handleMul($instruction['args']);
            case 'IDIV':
                return $this->handleIdiv($instruction['args']);
            case 'LT':
                return $this->handleLt($instruction['args']);
            case 'GT':
                return $this->handleGt($instruction['args']);
            case 'EQ':
                return $this->handleEq($instruction['args']);
            case 'AND':
                return $this->handleAnd($instruction['args']);
            case 'OR':
                return $this->handleOr($instruction['args']);
            case 'NOT':
                return $this->handleNot($instruction['args']);
            case 'INT2CHAR':
                return $this->handleInt2Char($instruction['args']);
            case 'STRI2INT':
                return $this->handleStri2Int($instruction['args']);
            case 'READ':
                return $this->handleRead($instruction['args']);
            case 'WRITE':
                return $this->handleWrite($instruction['args']);
            case 'CONCAT':
                return $this->handleConcat($instruction['args']);
            case 'STRLEN':
                return $this->handleStrlen($instruction['args']);
            default:
                ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
                return null;
        }
    }

    /**
     * Determines the value of the argument
     * @param array<mixed> $arg Argument
     * @return mixed Value of the argument
     */
    protected function determineValue($arg): mixed
    {
        switch ($arg['dataType']) {
            case 'var':
                return $this->getVariableValue($arg['value']);
                break;
            case 'int':
                return (int)$arg['value'];
            case 'bool':
                // convert string to boolean (every string except 'true' is false)
                return $arg['value'] === 'true' ? 'true' : 'false';
            case 'float':
                return (float)$arg['value'];
            case 'nil':
                return '';
                break;
            default:
                return $arg['value'];
        }
    }

    /**
     * Returns reference to the frame by its type
     * 
     * @param string $frameType Type of the frame
     * @return array<mixed>|null Reference to the frame
     * @throws \Exception If the frame type is invalid
     */
    protected function &getFrame($frameType) : ?array
    {
        switch ($frameType) {
            case 'GF':
                return $this->globalFrame;
            case 'TF':
                return $this->tempFrame;
            case 'LF':
                // return current local frame if exists
                if (!empty($this->frameStack)) {
                    return end($this->frameStack);
                } else {
                    ErrorHandler::handleException(ReturnCode::FRAME_ACCESS_ERROR);
                    return null;
                }
            default:
                ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
                return null;
        }
    }

    /**
     * Returns the value of the variable
     * 
     * @param string $fullVarName Full name of the variable
     * @return mixed Value of the variable
     * @throws \Exception If the variable is not found
     */
    protected function getVariableValue($fullVarName)
    {
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = $this->getFrame($frameType);
        if (isset($frame[$varName])) {
            return $frame[$varName];
        } else {
            ErrorHandler::handleException(ReturnCode::VARIABLE_ACCESS_ERROR);
            return null;
        }
    }

    /**
     * Sets the value of the variable
     * 
     * @param string $fullVarName Full name of the variable
     * @param mixed $value Value to set
     * @return void
     */
    protected function setVariableValue($fullVarName, $value)
    {
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = &$this->getFrame($frameType);

        $frame[$varName] = $value;
        print_r($frame);
    }

    /**
     * Handling MOVE instruction
     * @param array<mixed> $args Arguments of the instruction
     * @return null
     * @throws \Exception If the number of arguments is not 2
     */
     protected function handleMove(array $args): ?string
     {
         HelperFunctions::CheckArgs($args, 2);
 
         $targetVarName = $args[0]['value'];
         $sourceValue = $this->determineValue($args[1]);
 
         $this->setVariableValue($targetVarName, $sourceValue);
 
         return null;
     }

    /**
     * Handling CREATEFRAME instruction
     * @return null
     */
    protected function handleCreateFrame()
    {
        $this->tempFrame = []; // create new TF
        return null;
    }

    /**
     * Handling PUSHFRAME instruction
     * @return null
     */
    protected function handlePushFrame()
    {
        if ($this->tempFrame === null) {
            ErrorHandler::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        array_push($this->frameStack, $this->tempFrame); // Put TF on the stack
        $this->tempFrame = null; // clear TF

        return null;
    }

    /**
     * Handling POPFRAME instruction
     * @return null
     */
    protected function handlePopFrame()
    {
        if (empty($this->frameStack)) {
            ErrorHandler::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        $this->tempFrame = array_pop($this->frameStack); // Put TF from the stack to TF

        return null;
    }

    /**
     * Handling DEFVAR instruction
     * 
     * @param array<mixed> $args Arguments of the instruction
     * @return null 
     * @throws \Exception If the number of arguments is not 1
     */
    protected function handleDefvar(array $args): ?string
    {
        HelperFunctions::CheckArgs($args, 1);

        $fullVarName = $args[0]['value'];
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = &$this->getFrame($frameType);

        if (array_key_exists($varName, $frame)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        // New variable is created with value NULL and without type
        $frame[$varName] = null;

        print_r($frame);
        return null;
    }

    /**
     * Handling CALL instruction
     * 
     * @param array<mixed> $args Arguments of the instruction
     * @return null
     */
    protected function handleCall(array $args): ?string
    {
        $label = $args[0]['value'];
        if (!array_key_exists($label, $this->labels)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        array_push($this->callStack, $this->instructionIndex + 1);
        $this->instructionIndex = $this->labels[$label];
        $this->indexModified = true;
        return null;
    }
    
    /**
     * Handling RETURN instruction
     * 
     * @return null
     * @throws \Exception If the call stack is empty
     */
    protected function handleReturn()
    {
        if (empty($this->callStack)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->instructionIndex = array_pop($this->callStack);
        $this->indexModified = true;

        return null;
    }

    /**
     * Handling LABEL instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the label already exists
     */
    protected function handleLabel(array $args)
    {
        $labelName = $args[0]['value'];
        if (array_key_exists($labelName, $this->labels)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->labels[$labelName] = $this->instructionIndex;

        return null;
    }

    /**
     * Handling LABEL instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     */
    protected function handlePushs(array $args)
    {
        $value = $this->determineValue($args[0]);
        array_push($this->dataStack, $value);
        return null;
    }

    /**
     * Handling LABEL instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handlePops(array $args)
    {
        if (empty($this->dataStack)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        
        $value = array_pop($this->dataStack);
        $this->setVariableValue($args[0]['value'], $value);

        return null;
    }

    /**
     * Handling ADD instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handleAdd(array $args)
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);
        
        //TODO: check how to compare data types cause now its autoconveting
        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value + $symb2Value;
        $this->setVariableValue($varName, $result);
        return null;
    }

    /**
     * Handling SUB instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handleSub(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value - $symb2Value;
        $this->setVariableValue($varName, $result);
        return null;
    }

    /**
     * Handling MUL instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handleMul(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value * $symb2Value;
        $this->setVariableValue($varName, $result);

        return null;
    }

    /**
     * Handling IDIV instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handleIdiv(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($symb2Value === 0) {
            ErrorHandler::handleException(ReturnCode::OPERAND_VALUE_ERROR);
        }

        $result = intdiv($symb1Value, $symb2Value);
        $this->setVariableValue($varName, $result);

        return null;
    }

    /**
     * Helper function for comparing two values
     * 
     * @param mixed $value1 First value
     * @param mixed $value2 Second value
     * @param string $operator Comparison operator (LT, GT, EQ)
     * @return bool
     * @throws \Exception If the data stack is empty
     */
    protected function compareValues($value1, $value2, string $operator): bool
    {
        if (gettype($value1) !== gettype($value2)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        switch ($operator) {
            case 'LT':
                return $value1 < $value2;
            case 'GT':
                return $value1 > $value2;
            case 'EQ':
                return $value1 === $value2;
            default:
                ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
                return false;
        }
    }   

    /**
     * Handling LT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     */
    protected function handleLt(array $args)
    {
        return $this->handleComparison($args, 'LT');
    }

    /**
     * Handling GT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     */
    protected function handleGt(array $args)
    {
        return $this->handleComparison($args, 'GT');
    }

    /**
     * Handling EQ instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     */
    protected function handleEq(array $args)
    {
        return $this->handleComparison($args, 'EQ');
    }

    /**
     * Helper function for handling comparison instructions
     * 
     * @param array<mixed> $args Array of arguments
     * @param string $operator Comparison operator (LT, GT, EQ)
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleComparison(array $args, string $operator)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if ($symb1Value === null || $symb2Value === null) {
            if ($operator !== 'EQ' || ($symb1Value !== null || $symb2Value !== null)) {
                ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }

        $result = $this->compareValues($symb1Value, $symb2Value, $operator);
        $this->setVariableValue($varName, $result);
        print($result);

        return null;
    }

    /**
     * Handling AND instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleAnd(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);
        if (!is_bool($symb1Value) || !is_bool($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value && $symb2Value;
        $this->setVariableValue($varName, $result);

        return null;
    }

    /**
     * Handling OR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleOr(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);
        if (!is_bool($symb1Value) || !is_bool($symb2Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value || $symb2Value;
        $this->setVariableValue($varName, $result);

        return null;
    }

    /**
     * Handling NOT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleNot(array $args)
    {
        if (count($args) != 2) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        if (!is_bool($symb1Value)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = !$symb1Value;
        $this->setVariableValue($varName, $result);

        return null;
    }

    /**
     * Handling INT2CHAR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleInt2Char(array $args)
    {
        if (count($args) != 2) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $symbValue = $this->determineValue($args[1]);

        if (!is_int($symbValue)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        // Проверяем, является ли значение допустимым Unicode кодом символа
        if ($symbValue < 0 || $symbValue > 0x10FFFF) {
            ErrorHandler::handleException(ReturnCode::STRING_OPERATION_ERROR); // Используйте соответствующий код ошибки
        }

        $char = mb_chr($symbValue, 'UTF-8');
        $this->setVariableValue($varName, $char);

        return null;
    }

    /**
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleStri2Int(array $args)
    {
        if (count($args) != 3) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $string = $this->determineValue($args[1]);
        $position = $this->determineValue($args[2]);

        if (!is_string($string) || !is_int($position)) {
            ErrorHandler::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        // Проверка на выход за пределы строки
        if ($position < 0 || $position >= mb_strlen($string, 'UTF-8')) {
            ErrorHandler::handleException(ReturnCode::STRING_OPERATION_ERROR); // Используйте соответствующий код ошибки
        }

        $char = mb_substr($string, $position, 1, 'UTF-8');
        $ordValue = mb_ord($char, 'UTF-8');
        $this->setVariableValue($varName, $ordValue);

        return null;
    }

    /**
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleRead(array $args)
    {
        if (count($args) != 2) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $varName = $args[0]['value'];
        $type = $args[1]['value'];

        // Инициализация значения переменной nil в случае ошибки чтения
        $value = 'nil@nil';

        // Чтение значения из входного потока в соответствии с типом
        switch ($type) {
            case 'int':
                $readValue = $this->inputReader->readInt();
                if ($readValue !== null) {
                    $value = $readValue;
                }
                break;
            case 'bool':
                $readValue = $this->inputReader->readBool();
                if ($readValue !== null) {
                    $value = $readValue;
                }
                break;
            case 'string':
                $readValue = $this->inputReader->readString();
                if ($readValue !== null) {
                    $value = $readValue;
                }
                break;
            default:
                break;
        }

        $this->setVariableValue($varName, $value);

        return null;
    }

    /**
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return string
     * @throws \Exception If arguments are invalid
     */
    protected function handleWrite(array $args): string
    {
        if (count($args) != 1) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $value = $this->determineValue($args[0]);

        return $value;
    }

    /**
     * Handling CONCAT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleConcat(array $args)
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        $result = $symb1Value . $symb2Value;
        $this->setVariableValue($varName, $result);
        return null;
    }

    /**
     * Handling STRLEN instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If arguments are invalid
     */
    protected function handleStrlen(array $args)
    {
        HelperFunctions::CheckArgs($args, 2);

        $varName = $args[0]['value'];
        $stringValue = $this->determineValue($args[1]);

        $result = mb_strlen($stringValue, "UTF-8");
        $this->setVariableValue($varName, $result);

        return null;
    }
}
