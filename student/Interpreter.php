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
use IPP\Core\Settings;
use IPP\Core\FileInputReader;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {   
        $helperFunctions = new HelperFunctions();
        //Intialize the source analyzer
        $sourceAnalyzer = new XMLAnalyzer($this->source->getDOMDocument());
        $instructions = $sourceAnalyzer->analyze();

        $sorter = new InstructionSorter();
        $sortedInstructions = $sorter->sortInstructions($instructions);

        $settings = new ExtendedSettings();
        $settings->processArgs();

        $inputReader = $settings->getInputReader();

        $stdoutWritter = new StreamWriter(STDOUT);
        $stderrWritter = new StreamWriter(STDERR);

        $resultOutputter = new ResultOutputter($stdoutWritter, $stderrWritter);

        $processor = new InstructionProcessor($inputReader, $resultOutputter);

        if (empty($sortedInstructions)) {
            return 0; // Success
        }
        // Initialize instruction index
        $processor->instructionIndex = min(array_keys($sortedInstructions));

        // handle labels
        $processor->checkLabels($sortedInstructions);

        // handle every instruction
        while ($processor->instructionIndex <= max(array_keys($sortedInstructions))) {
            if (isset($sortedInstructions[$processor->instructionIndex])) {
                $currentInstruction = $sortedInstructions[$processor->instructionIndex];
                $processor->processInstruction($currentInstruction);
            }
            
            // increment instruction index if it was not modified
            if (!$processor->indexModified) {
                // set index to the next instruction
                $currentKeys = array_keys($sortedInstructions);
                $currentIndexKey = array_search($processor->instructionIndex, $currentKeys);
                
                // check if there is a next instruction
                if (isset($currentKeys[$currentIndexKey + 1])) {
                    $processor->instructionIndex = $currentKeys[$currentIndexKey + 1];
                } else {
                    break; // if there is no next instruction, break the loop
                }
            }

            $processor->indexModified = false;
        }

        return 0; // Success
    }
}