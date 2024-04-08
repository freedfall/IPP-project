<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // Import and analyze XML source file
        $dom = $this->source->getDOMDocument();
        $xpath = new \DOMXPath($dom);

        // Collect and sort all instructions by the order attribute
        $instructions = $xpath->query('/program/instruction');
        $sortedInstructions = $this->sortInstructions($instructions);

        // Handle each instruction
        foreach ($sortedInstructions as $instruction) {
            $result = $this->processInstruction($instruction);
            if ($result !== null) {
                $this->outputResult($result);
            }
        }

        $val = $this->input->readString();
        $this->stdout->writeString("stdout");
        $this->stderr->writeString("stderr");
        throw new NotImplementedException;
    }
    protected function processInstruction($instruction): ?string {
        $opcode = strtoupper($instruction->getAttribute('opcode'));
        switch ($opcode) {
            case 'WRITE':
                return $this->handleWrite($instruction);
            // Обработка других инструкций...
            default:
                // Обработка неизвестной инструкции или инструкций без вывода
                return null;
        }
    }
}
