<?php
/**
 * IPP Interpreter
 * Class for processing instructions
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use DOMElement;
use IPP\Core\ReturnCode;
use IPP\Core\FileInputReader;
use IPP\Core\Interface\InputReader;

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

    /**
     * @var ResultOutputter Result Outputter
     */
    protected ResultOutputter $resultOutputter;

    public int $instructionIndex = 0;
    public bool $indexModified = false;


    public function __construct(InputReader $inputReader, ResultOutputter $resultOutputter){
        $this->inputReader = $inputReader;
        $this->resultOutputter = $resultOutputter;
    }
    /**
     * Processes the instruction.
     *
     * @param array{opcode: string, args: array<mixed>} $instruction Instruction to process, where:
     *        - 'opcode' is the operation code (string) of the instruction,
     *        - 'args' is an array of arguments for the instruction. The type of each argument can vary, hence 'mixed'.
     * @throws \Exception If the instruction is unknown or an error occurs.
     */
    public function processInstruction(array $instruction): void
    {

        switch (strtoupper($instruction['opcode'])) {
            case 'MOVE':
                $this->handleMove($instruction['args']);
                break;
            case 'CREATEFRAME':
                $this->handleCreateFrame();
                break;
            case 'PUSHFRAME':
                $this->handlePushFrame();
                break;
            case 'POPFRAME':
                $this->handlePopFrame();
                break;
            case 'DEFVAR':
                $this->handleDefvar($instruction['args']);
                break;
            case 'CALL':
                $this->handleCall($instruction['args']);
                break;
            case 'RETURN':
                $this->handleReturn();
                break;
            case 'LABEL':
                break;
            case 'PUSHS':
                $this->handlePushs($instruction['args']);
                break;
            case 'POPS':
                $this->handlePops($instruction['args']);
                break;
            case 'ADD':
                $this->handleAdd($instruction['args']);
                break;
            case 'SUB':
                $this->handleSub($instruction['args']);
                break;
            case 'MUL':
                $this->handleMul($instruction['args']);
                break;
            case 'IDIV':
                $this->handleIdiv($instruction['args']);
                break;
            case 'LT':
                $this->handleComparison($instruction['args'], 'LT');
                break;
            case 'GT':
                $this->handleComparison($instruction['args'], 'GT');
                break;
            case 'EQ':
                $this->handleComparison($instruction['args'], 'EQ');
                break;
            case 'AND':
                $this->handleAnd($instruction['args']);
                break;
            case 'OR':
                $this->handleOr($instruction['args']);
                break;
            case 'NOT':
                $this->handleNot($instruction['args']);
                break;
            case 'INT2CHAR':
                $this->handleInt2Char($instruction['args']);
                break;
            case 'STRI2INT':
                $this->handleStri2Int($instruction['args']);
                break;
            case 'READ':
                $this->handleRead($instruction['args']);
                break;
            case 'WRITE':
                $this->handleWrite($instruction['args']);
                break;
            case 'CONCAT':
                $this->handleConcat($instruction['args']);
                break;
            case 'STRLEN':
                $this->handleStrlen($instruction['args']);
                break;
            case 'GETCHAR':
                $this->handleGetchar($instruction['args']);
                break;
            case 'SETCHAR':
                $this->handleSetchar($instruction['args']);
                break;
            case 'TYPE':
                $this->handleType($instruction['args']);
                break;
            case 'JUMP':
                $this->handleJump($instruction['args']);
                break;
            case 'JUMPIFEQ':
                $this->handleJumpifeq($instruction['args']);
                break;
            case 'JUMPIFNEQ':
                $this->handleJumpifneq($instruction['args']);
                break;
            case 'EXIT':
                $this->handleExit($instruction['args']);
                break;
            default:
                HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
    }
    /**
     * Add all the labels from document into array
     * 
     * @param array<mixed> $instructions Sorted instruction array
     */
    public function checkLabels(array $instructions): void
    {
        foreach ($instructions as $instruction){
            if (strtoupper($instruction['opcode']) === 'LABEL'){
                $label = $instruction['args'][0]['value'];
                if (array_key_exists($label, $this->labels)){
                    HelperFunctions::HandleException(ReturnCode::SEMANTIC_ERROR);
                }
                $this->labels[$label] = $instruction['order'];
            }
            
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
            case 'int':
                return (int)$arg['value'];
            case 'bool':
                // convert string to boolean (every string except 'true' is false)
                return $arg['value'] === 'true' ? true : false;
            case 'float':
                return (float)$arg['value'];
            case 'nil':
                return 'nil@nil';
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
                    HelperFunctions::handleException(ReturnCode::FRAME_ACCESS_ERROR);
                    return null;
                }
            default:
                HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
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
            HelperFunctions::handleException(ReturnCode::VARIABLE_ACCESS_ERROR);
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
     * @return void
     * @throws \Exception If the number of arguments is not 2
     */
     protected function handleMove(array $args): void
     {
         HelperFunctions::CheckArgs($args, 2);
 
         $targetVarName = $args[0]['value'];
         $sourceValue = $this->determineValue($args[1]);
 
         $this->setVariableValue($targetVarName, $sourceValue);
     }

    /**
     * Handling CREATEFRAME instruction
     * @return void
     */
    protected function handleCreateFrame()
    {
        $this->tempFrame = []; // create new TF
    }

    /**
     * Handling PUSHFRAME instruction
     * @return void
     */
    protected function handlePushFrame()
    {
        if ($this->tempFrame === null) {
            HelperFunctions::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        array_push($this->frameStack, $this->tempFrame); // Put TF on the stack
        $this->tempFrame = null; // clear TF
    }

    /**
     * Handling POPFRAME instruction
     * @return void
     */
    protected function handlePopFrame()
    {
        if (empty($this->frameStack)) {
            HelperFunctions::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        $this->tempFrame = array_pop($this->frameStack); // Put TF from the stack to TF
    }

    /**
     * Handling DEFVAR instruction
     * 
     * @param array<mixed> $args Arguments of the instruction
     * @return void
     * @throws \Exception If the number of arguments is not 1
     */
    protected function handleDefvar(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 1);

        $fullVarName = $args[0]['value'];
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = &$this->getFrame($frameType);

        if (array_key_exists($varName, $frame)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        // New variable is created with value NULL and without type
        $frame[$varName] = null;

        print_r($frame);
    }

    /**
     * Handling CALL instruction
     * 
     * @param array<mixed> $args Arguments of the instruction
     * @return void
     */
    protected function handleCall(array $args) : void
    {
        $label = $args[0]['value'];
        if (!array_key_exists($label, $this->labels)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        array_push($this->callStack, $this->instructionIndex + 1);
        $this->instructionIndex = $this->labels[$label];
        $this->indexModified = true;
    }
    
    /**
     * Handling RETURN instruction
     * 
     * @return void
     * @throws \Exception If the call stack is empty
     */
    protected function handleReturn() : void
    {
        if (empty($this->callStack)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->instructionIndex = array_pop($this->callStack);
        $this->indexModified = true;

    }

    /**
     * Handling PUSHS instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     */
    protected function handlePushs(array $args) : void
    {
        $value = $this->determineValue($args[0]);
        array_push($this->dataStack, $value);
    }

    /**
     * Handling POPS instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return null
     * @throws \Exception If the data stack is empty
     */
    protected function handlePops(array $args) : void
    {
        if (empty($this->dataStack)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        
        $value = array_pop($this->dataStack);
        $this->setVariableValue($args[0]['value'], $value);

    }

    /**
     * Handling ADD instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If the data stack is empty
     */
    protected function handleAdd(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);
        
        //TODO: check how to compare data types cause now its autoconveting
        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value + $symb2Value;
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling SUB instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If the data stack is empty
     */
    protected function handleSub(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value - $symb2Value;
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling MUL instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If the data stack is empty
     */
    protected function handleMul(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);


        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value * $symb2Value;
        $this->setVariableValue($varName, $result);

    }

    /**
     * Handling IDIV instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If the data stack is empty
     */
    protected function handleIdiv(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);


        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_int($symb1Value) || !is_int($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        if ($symb2Value === 0) {
            HelperFunctions::handleException(ReturnCode::OPERAND_VALUE_ERROR);
        }

        $result = intdiv($symb1Value, $symb2Value);
        $this->setVariableValue($varName, $result);
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
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        switch ($operator) {
            case 'LT':
                return $value1 < $value2;
            case 'GT':
                return $value1 > $value2;
            case 'EQ':
                return $value1 === $value2;
            default:
                HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
                return false;
        }
    }   

    /**
     * Helper function for handling comparison instructions
     * 
     * @param array<mixed> $args Array of arguments
     * @param string $operator Comparison operator (LT, GT, EQ)
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleComparison(array $args, string $operator) : void
    {
        HelperFunctions::checkArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if ($symb1Value === null || $symb2Value === null) {
            if ($operator !== 'EQ' || ($symb1Value !== null || $symb2Value !== null)) {
                HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
            }
        }

        $result = $this->compareValues($symb1Value, $symb2Value, $operator);
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling AND instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleAnd(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_bool($symb1Value) || !is_bool($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value && $symb2Value;
        $this->setVariableValue($varName, $result);

    }

    /**
     * Handling OR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleOr(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!is_bool($symb1Value) || !is_bool($symb2Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = $symb1Value || $symb2Value;
        $this->setVariableValue($varName, $result);

    }

    /**
     * Handling NOT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleNot(array $args) : void
    {
        HelperFunctions::checkArgs($args, 2);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        if (!is_bool($symb1Value)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        $result = !$symb1Value;
        $this->setVariableValue($varName, $result);

    }

    /**
     * Handling INT2CHAR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleInt2Char(array $args) : void
    {
        HelperFunctions::checkArgs($args, 2);

        $varName = $args[0]['value'];
        $symbValue = $this->determineValue($args[1]);

        if (!is_int($symbValue)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        // check if value is okay
        if ($symbValue < 0 || $symbValue > 0x10FFFF) {
            HelperFunctions::handleException(ReturnCode::STRING_OPERATION_ERROR);
        }

        $char = mb_chr($symbValue, 'UTF-8');
        $this->setVariableValue($varName, $char);

    }

    /**
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleStri2Int(array $args) : void
    {
        HelperFunctions::checkArgs($args, 3);

        $varName = $args[0]['value'];
        $string = $this->determineValue($args[1]);
        $position = $this->determineValue($args[2]);

        if (!is_string($string) || !is_int($position)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        // check if position is okay
        if ($position < 0 || $position >= mb_strlen($string, 'UTF-8')) {
            HelperFunctions::handleException(ReturnCode::STRING_OPERATION_ERROR); 
        }

        $char = mb_substr($string, $position, 1, 'UTF-8');
        $ordValue = mb_ord($char, 'UTF-8');
        $this->setVariableValue($varName, $ordValue);
    }

    /**
     * TODO check how it really works
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleRead(array $args) : void
    {
        HelperFunctions::checkArgs($args, 2);

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
    }

    /**
     * Handling STR2INT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleWrite(array $args): void
    {
        if (count($args) != 1) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $value = $this->determineValue($args[0]);

        if ($value != null){
            $this->resultOutputter->outputResult($value);
        }
    }

    /**
     * Handling CONCAT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleConcat(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        $result = $symb1Value . $symb2Value;
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling STRLEN instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleStrlen(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 2);

        $varName = $args[0]['value'];
        $stringValue = $this->determineValue($args[1]);

        $result = mb_strlen($stringValue, "UTF-8");
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling GETCHAR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleGetchar(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $args[0]['value'];
        $stringValue = $this->determineValue($args[1]);
        $indexValue = $this->determineValue($args[2]);

        if (!is_string($stringValue) || !is_int($indexValue)) {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        HelperFunctions::checkIndex($stringValue, $indexValue);

        $result = mb_substr($stringValue, $indexValue, 1, "UTF-8");
        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling SETCHAR instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleSetchar(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $varName = $this->getVariableValue($args[0]['value']);
        $position = $this->determineValue($args[1]);
        $replacement = $this->determineValue($args[2]);

        if (!is_string($varName) || !is_int($position) || !is_string($replacement) || $replacement === '') {
            HelperFunctions::handleException(ReturnCode::OPERAND_TYPE_ERROR);
        }

        HelperFunctions::checkIndex($varName, $position);

        //take first symbol from string if there is > 1
        $charToInsert = mb_substr($replacement, 0, 1, "UTF-8");
        $result = mb_substr($varName, 0, $position, "UTF-8") . $charToInsert . mb_substr($varName, $position + 1, mb_strlen($varName, "UTF-8") - $position - 1, "UTF-8");

        $this->setVariableValue($args[0]['value'], $result);
    }

    /**
     * Handling TYPE instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleType(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 2);

        $varName = $args[0]['value'];
        $symbValue = $this->determineValue($args[1]);

        $result = HelperFunctions::getDataType($symbValue);

        $this->setVariableValue($varName, $result);
    }

    /**
     * Handling JUMP instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleJump(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 1);

        $label = $args[0]['value'];
        if (!array_key_exists($label, $this->labels)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->instructionIndex = $this->labels[$label];
        $this->indexModified = true;
    }

    /**
     * Handling JUMPIFEQ instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleJumpifeq(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $label = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!array_key_exists($label, $this->labels)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        if ($symb1Value === $symb2Value){
            $this->instructionIndex = $this->labels[$label];
            $this->indexModified = true;
        }
    }

    /**
     * Handling JUMPIFNEQ instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleJumpifneq(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 3);

        $label = $args[0]['value'];
        $symb1Value = $this->determineValue($args[1]);
        $symb2Value = $this->determineValue($args[2]);

        if (!array_key_exists($label, $this->labels)) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        if ($symb1Value != $symb2Value){
            $this->instructionIndex = $this->labels[$label];
            $this->indexModified = true;
        }
    }

    /**
     * Handling EXIT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleExit(array $args) : void
    {
        HelperFunctions::CheckArgs($args, 1);

        $symb1Value = $this->determineValue($args[0]);

        if ($symb1Value < 0 || $symb1Value > 9){
            HelperFunctions::handleException(ReturnCode::OPERAND_VALUE_ERROR);
        }
        exit($symb1Value);
    }

    /**
     * Handling DPRINT instruction
     * 
     * @param array<mixed> $args Array of arguments
     * @return void
     * @throws \Exception If arguments are invalid
     */
    protected function handleDprint(array $args): void
    {
        if (count($args) != 1) {
            HelperFunctions::handleException(ReturnCode::SEMANTIC_ERROR);
        }

        $value = $this->determineValue($args[0]);
        $this->resultOutputter->outputError($value);
    }
}
