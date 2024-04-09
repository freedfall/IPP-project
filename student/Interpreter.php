<?php
/**
 * IPP Interpreter
 * Main class for interpreting
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\StreamWriter;
use IPP\Student\InstructionProcessor;
use IPP\Student\InstructionSorter;
use IPP\Student\XMLAnalyzer;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        //Intialize the source analyzer
        $sourceAnalyzer = new XMLAnalyzer($this->source->getDOMDocument());
        $instructions = $sourceAnalyzer->analyze();

        $sorter = new InstructionSorter();
        $sortedInstructions = $sorter->sortInstructions($instructions);

        $processor = new InstructionProcessor();
        $stdoutWriter = new StreamWriter(STDOUT);
        $stderrWriter = new StreamWriter(STDERR);

        // Initialize instruction index
        $processor->instructionIndex = 0;

        // Handle each instruction according to the instruction index
        while ($processor->instructionIndex < count($sortedInstructions)) {
            $currentInstruction = $sortedInstructions[$processor->instructionIndex];
            $result = $processor->processInstruction($currentInstruction);
            if ($result !== null) {
                $stdoutWriter->writeString($result);
            }

            // Manually increment the instruction index if not modified by CALL or RETURN
            if (!$processor->indexModified) {
                $processor->instructionIndex++;
            }
            // Reset index modification flag for the next iteration
            $processor->indexModified = false;
        }

        return 0; // Success
    }
}