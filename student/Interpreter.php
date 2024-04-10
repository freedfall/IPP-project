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

        $settings = new Settings();
        $settings->processArgs();

        $inputReader = $settings->getInputReader();

        $stdoutWritter = new StreamWriter(STDOUT);
        $stderrWritter = new StreamWriter(STDERR);

        $resultOutputter = new ResultOutputter($stdoutWritter, $stderrWritter);

        $processor = new InstructionProcessor($inputReader, $resultOutputter);


        // Initialize instruction index
        $processor->instructionIndex = 0;
        $processor->checkLabels($sortedInstructions);

        // Handle each instruction according to the instruction index
        while ($processor->instructionIndex < count($sortedInstructions)) {
            $currentInstruction = $sortedInstructions[$processor->instructionIndex];
            $processor->processInstruction($currentInstruction);

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