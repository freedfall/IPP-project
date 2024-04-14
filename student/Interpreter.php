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
    public ?StatisticsCollector $statisticsCollector = null;

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

        //create statistics collector even if stats are not enabled to avoid errors
        $this->statisticsCollector = new StatisticsCollector($settings->statsFile);

        //set statistics collector settings
        if ($settings->stats) {
            if ($settings->countInstructions) {
                $this->statisticsCollector->collectInstructions = true;
            }

            if ($settings->hot) {
                $this->statisticsCollector->collectHotInstruction = true;
            }

            if ($settings->vars) {
                $this->statisticsCollector->collectMaxVars = true;
            }

            if ($settings->stack) {
                $this->statisticsCollector->collectMaxStack = true;
            }
        }

        $inputReader = $settings->getInputReader();

        $stdoutWritter = new StreamWriter(STDOUT);
        $stderrWritter = new StreamWriter(STDERR);

        $resultOutputter = new ResultOutputter($stdoutWritter, $stderrWritter);

        $processor = new InstructionProcessor($inputReader, $resultOutputter, $this->statisticsCollector);

        // if there are no instructions, return 0
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

                // handling statistics
                // increment instruction count if it is needed
                if ($settings->countInstructions && $currentInstruction['opcode'] !== 'LABEL' && $currentInstruction['opcode'] !== 'DRPINT' && $currentInstruction['opcode'] !== 'BREAK') {
                    $this->statisticsCollector->instructionCount++;
                }
                // find the most executed instruction order if it is needed
                if ($settings->hot) {
                    $instructionName = strtoupper($currentInstruction['opcode']);

                    $this->statisticsCollector->updateExecutedOrder($instructionName);

                    if (!isset($this->statisticsCollector->firstOrderEncountered[$instructionName])) {
                        $this->statisticsCollector->firstOrderEncountered[$instructionName] = $currentInstruction['order'];
                    }
                }

                // update variable count if it is needed
                if ($settings->vars) {
                    $this->statisticsCollector->updateVariableCount($processor->countVars());
                }

                // update stack size if it is needed
                if ($settings->stack){
                    $this->statisticsCollector->updateStackSize(count($processor->dataStack));
                }

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

        // output statistics if it is needed
        if ($settings->stats) {
            $this->statisticsCollector->saveStatistics($settings->statParams);
        }

        return 0; // Success
    }
}