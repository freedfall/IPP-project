<?php
/**
 * IPP Interpreter
 * Class for collecting statistics
 * @author Timur Kininbayev (xkinin00)
 * 
 */

namespace IPP\Student;

class StatisticsCollector {
    public bool $collectInstructions = false;
    public bool $collectHotInstruction = false;
    public bool $collectMaxVars = false;
    public bool $collectMaxStack = false;

    private int $instructionCount = 0;
    private int $maxVariables = 0;
    private int $maxStackSize = 0;
    private string $statsFile;
    /**
     * @var array<int>
     */
    private array $executedOrderCounts = [];


    public function __construct(string $statsFile) {
        $this->statsFile = $statsFile;
    }

    public function setCollectInstructions(): void {
        $this->collectInstructions = true;
    }

    public function setCollectHotInstruction(): void {
        $this->collectHotInstruction = true;
    }

    public function setCollectMaxVars(): void {
        $this->collectMaxVars = true;
    }

    public function setCollectMaxStack(): void {
        $this->collectMaxStack = true;
    }

    public function increaseInstructionCount(): void {
        $this->instructionCount++;
    }

    public function updateExecutedOrder(int $order): void {
        if (!isset($this->executedOrderCounts[$order])) {
            $this->executedOrderCounts[$order] = 0;
        }
        $this->executedOrderCounts[$order]++;
    }

    public function updateVariableCount(int $currentCount): void {
        if ($currentCount > $this->maxVariables) {
            $this->maxVariables = $currentCount;
        }
    }

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
        foreach ($statParams as $param) {
            switch ($param) {
                case '--insts':
                    $lines[] = "Total instructions executed: {$this->instructionCount}";
                    break;
                case '--hot':
                    $mostExecutedOrder = array_search(max($this->executedOrderCounts), $this->executedOrderCounts);
                    $lines[] = "Most frequently executed instruction order: {$mostExecutedOrder}";
                    break;
                case '--vars':
                    $lines[] = "Maximum variables at any point: {$this->maxVariables}";
                    break;
                case '--stack':
                    $lines[] = "Maximum stack size: {$this->maxStackSize}";
                    break;
                case '--print':
                    $customMessage = substr($param, strlen('--print='));
                    $lines[] = "Custom print message: {$customMessage}";
                    break;
                case '--eol':
                    $lines[] = "\n";
                    break;
            }
        }
        file_put_contents($this->statsFile, implode("\n", $lines), FILE_APPEND);
    }
}
