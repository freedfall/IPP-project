<?php

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
}
