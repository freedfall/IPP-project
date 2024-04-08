<?php
/**
 * IPP Interpreter
 * Class for processing instructions
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use DOMElement;

class InstructionProcessor
{
    protected $globalFrame = [];
    protected $tempFrame = null;
    protected $frameStack = [];
    protected $callStack = [];

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
            default:
                throw new \Exception("Unknown instruction: " . $instruction['opcode']);
        }
    }

    protected function handleMove(array $args): ?string
    {
        // Реализация логики MOVE
    }

    protected function handleCreateFrame(): ?string
    {
        $this->tempFrame = []; // Создаём новый временный фрейм
    }

    protected function handlePushFrame(): ?string
    {
        if ($this->tempFrame === null) {
            throw new \Exception("Temporary frame not defined", 55);
        }
        array_push($this->frameStack, $this->tempFrame); // Put TF on the stack
        $this->tempFrame = null; // clear TF
    }

    protected function handlePopFrame(): ?string
    {
        if (empty($this->frameStack)) {
            throw new \Exception("Frame stack is empty", 55);
        }
        $this->tempFrame = array_pop($this->frameStack); // Put TF from the stack to TF
    }

    protected function handleDefvar(array $args): ?string
    {
        // Реализация логики DEFVAR
    }

    protected function handleCall(array $args): ?string
    {
        // Добавляем текущую позицию в стек вызовов
        array_push($this->callStack, /* текущая позиция + 1 */);
        // Изменяем текущую позицию на позицию метки (label), которую нужно реализовать
    }

    protected function handleReturn(): ?string
    {
        if (empty($this->callStack)) {
            throw new \Exception("Call stack is empty", 56);
        }
        // Восстанавливаем позицию из стека вызовов
        // $position = array_pop($this->callStack);
        // Изменяем текущую позицию на $position
    }
    protected function getFrame($frameType)
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
                    throw new \Exception("Local frame stack is empty.");
                }
            default:
                throw new \Exception("Invalid frame type: {$frameType}");
        }
    }
    protected function getVariableValue($fullVarName)
    {
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = $this->getFrame($frameType);

        if (isset($frame[$varName])) {
            return $frame[$varName];
        } else {
            throw new \Exception("Variable {$fullVarName} not found.");
        }
    }
    protected function setVariableValue($fullVarName, $value)
    {
        list($frameType, $varName) = explode('@', $fullVarName, 2);
        $frame = &$this->getFrame($frameType);

        $frame[$varName] = $value;
    }

}
