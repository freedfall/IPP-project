<?php
/**
 * IPP Interpreter
 * Class for collecting statistics
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student;

class StatisticsCollector {
    public bool $collectInstructions = false;
    public bool $collectHotInstruction = false;
    public bool $collectMaxVars = false;
    public bool $collectMaxStack = false;

    public int $instructionCount = 0;
    public int $maxVariables = 0;
    public int $maxStackSize = 0;
    private string $statsFile;

    /**
     * @var array<int>
     */
    private array $executedOrderCounts = [];

    /**
     * @var array<int>
     */
    public array $firstOrderEncountered = [];

    public function __construct(string $statsFile) {
        $this->statsFile = $statsFile;
    }

    /**
     * Function to update the instruction count of every instruction
     * @param string $instruction The instruction to update
     * @return void
     */
    public function updateExecutedOrder(string $instruction): void {
        if (!isset($this->executedOrderCounts[$instruction])) {
            $this->executedOrderCounts[$instruction] = 0;
        }
        $this->executedOrderCounts[$instruction]++;
    }

    /**
     * Function to set the first order encountered for an instruction
     * @param string $instruction The instruction to set
     * @param int $order The order to set
     * @return void
     */
    public function setFirstOrderEncountered(string $instruction, int $order): void {
        if (!isset($this->firstOrderEncountered[$instruction])) {
            $this->firstOrderEncountered[$instruction] = $order;
        }
    }

    /**
     * Function to update max variable count at any point
     * @param int $currentCount The current variable count
     * @return void
     */
    public function updateVariableCount(int $currentCount): void {
        if ($currentCount > $this->maxVariables) {
            $this->maxVariables = $currentCount;
        }
    }

    /**
     * Function to update max stack size at any point
     * @param int $currentStackSize The current stack size
     * @return void
     */
    public function updateStackSize(int $currentStackSize): void {
        if ($currentStackSize > $this->maxStackSize) {
            $this->maxStackSize = $currentStackSize;
        }
    }

    /**
     * Save statistics to file
     * @param array<string> $statParams
     */
    public function saveStatistics(array $statParams): void {
        $lines = [];
        $printRegex = '/--print=(.+)/';
        foreach ($statParams as $param) {
            switch ($param) {
                case '--insts':
                    $lines[] = "{$this->instructionCount}";
                    break;
                case '--hot':
                    $maxExecutions = max($this->executedOrderCounts);

                    // search for instructions with the maximum number of executions
                    $mostExecutedOrders = array_keys($this->executedOrderCounts, $maxExecutions);

                    // search for the first order of the most executed instructions
                    if (count($mostExecutedOrders) > 1) {
                        // if there is a conflict, take the instruction with the lowest order
                        $minOrder = min($mostExecutedOrders);
                    } else {
                        // if there is no conflict, take the only instruction
                        $minOrder = $mostExecutedOrders[0];
                    }

                    $lines[] = "{$this->firstOrderEncountered[$minOrder]}";
                    break;
                case '--vars':
                    $lines[] = "{$this->maxVariables}";
                    break;
                case '--stack':
                    $lines[] = "{$this->maxStackSize}";
                    break;
                case preg_match($printRegex, $param) ? true : false:
                    $customMessage = substr($param, strlen('--print='));
                    $lines[] = "{$customMessage}";
                    break;
                case '--eol':
                    $lines[] = "";
                    break;
            }
        }
        file_put_contents($this->statsFile, implode("\n", $lines), FILE_APPEND);
    }
}
