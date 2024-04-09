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
use IPP\Student\InstructionProcessor;
use IPP\Student\InstructionSorter;
use IPP\Student\ResultOutputter;
use IPP\Student\XMLSourceAnalyzer;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        //Intialize the source analyzer
        $sourceAnalyzer = new XMLSourceAnalyzer($this->source->getDOMDocument());
        $instructions = $sourceAnalyzer->analyze();

        $sorter = new InstructionSorter();
        $sortedInstructions = $sorter->sortInstructions($instructions);

        $processor = new InstructionProcessor();
        $outputter = new ResultOutputter($this->stdout, $this->stderr);

        //Handle each instruction
        foreach ($sortedInstructions as $instruction) {
            $result = $processor->processInstruction($instruction);
            if ($result !== null) {
                $outputter->outputResult($result);
            }
        }

        return 0; // Success
    }
}