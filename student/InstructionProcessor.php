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

/**
 * Class InstructionProcessor
 * 
 * Processes instructions
 */
class InstructionProcessor
{
    protected $globalFrame = [];
    protected $tempFrame = null;
    protected $frameStack = [];
    protected $callStack = [];
    protected $labels = [];
    protected $instructionIndex = 0;
    protected $indexModified = false;

    /**
     * Processes the instruction
     * 
     * @param array $instruction Instruction to process
     * @return string|null Result of the instruction
     * @throws \Exception If the instruction is unknown
     */
    public function processInstruction(array $instruction): ?string
    {
        $this->instructionIndex = $instruction['order'];
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
            default:
                ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
    }

    /**
     * Determines the value of the argument
     * @param array $arg Argument
     * @return mixed Value of the argument
     */
    protected function determineValue($arg): mixed
    {
        if ($arg['dataType'] === 'var') {
            // get the value of the variable
            return $this->getVariableValue($arg['value']);
        } else {
            return $arg['value'];
        }
    }

    /**
     * Returns reference to the frame by its type
     * 
     * @param string $frameType Type of the frame
     * @return array Reference to the frame
     * @throws \Exception If the frame type is invalid
     */
    protected function &getFrame($frameType)
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
                }
            default:
                ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
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
     * @param array $args Arguments of the instruction
     * @return null
     * @throws \Exception If the number of arguments is not 2
     */
     protected function handleMove(array $args): ?string
     {
         if (count($args) != 2) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
         }
 
         $targetVarName = $args[0]['value'];
         $sourceValue = $this->determineValue($args[1]);
 
         $this->setVariableValue($targetVarName, $sourceValue);
 
         return null;
     }

    /**
     * Handling CREATEFRAME instruction
     * @return null
     */
    protected function handleCreateFrame(): ?string
    {
        $this->tempFrame = []; // Создаём новый временный фрейм
    }

    /**
     * Handling PUSHFRAME instruction
     * @return null
     */
    protected function handlePushFrame(): ?string
    {
        if ($this->tempFrame === null) {
            ErrorHandler::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        array_push($this->frameStack, $this->tempFrame); // Put TF on the stack
        $this->tempFrame = null; // clear TF
    }

    /**
     * Handling POPFRAME instruction
     * @return null
     */
    protected function handlePopFrame(): ?string
    {
        if (empty($this->frameStack)) {
            ErrorHandler::handleException(ReturnCode::FRAME_ACCESS_ERROR);
        }
        $this->tempFrame = array_pop($this->frameStack); // Put TF from the stack to TF
    }

    /**
     * Handling DEFVAR instruction
     * 
     * @param array $args Arguments of the instruction
     * @return null 
     * @throws \Exception If the number of arguments is not 1
     */
    protected function handleDefvar(array $args): ?string
    {
        if (count($args) != 1) {
            throw new \Exception("DEFVAR requires exactly one argument.");
        }

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
     * @param array $args Arguments of the instruction
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
    protected function handleReturn(): ?string
    {
        if (empty($this->callStack)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->instructionIndex = array_pop($this->callStack);
        $this->indexModified = true;
    }
    protected function handleLabel(array $args): ?string
    {
        $labelName = $args[0]['value'];
        if (array_key_exists($labelName, $this->labels)) {
            ErrorHandler::handleException(ReturnCode::SEMANTIC_ERROR);
        }
        $this->labels[$labelName] = $this->instructionIndex;
    }
}
