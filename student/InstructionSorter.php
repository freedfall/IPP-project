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
        $sortedInstructions = [];

        foreach ($instructions as $instruction) {
            $order = (int) $instruction['order'];
            $sortedInstructions[$order] = $instruction;
        }
        ksort($sortedInstructions);
        return $sortedInstructions;
    }
}