<?php
/**
 * IPP Interpreter
 * Class for sorting instructions
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

class InstructionSorter
{
    public function sortInstructions(array $instructions): array
    {
        usort($instructions, function ($a, $b) {
            return (int)$a['order'] <=> (int)$b['order'];
        });
        return $instructions;
    }
}