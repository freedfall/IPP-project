<?php

namespace IPP\Student;

/**
     * Class Instruction Sorter
     */
class InstructionSorter
{
    /**
     * Sorting instruction array
     * 
     * @param array<mixed> $instructions - unsorted instruction array
     * @return array<mixed> sorted instruction array
     */
    public function sortInstructions(array $instructions): array
    {
        usort($instructions, function ($a, $b) {
            return (int)$a['order'] <=> (int)$b['order'];
        });
        return $instructions;
    }
}