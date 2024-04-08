<?php

namespace IPP\Student;

use DOMNodeList;

class InstructionSorter
{
    public function sortInstructions(DOMNodeList $instructions): array
    {
        $instructionsArray = iterator_to_array($instructions);
        usort($instructionsArray, function ($a, $b) {
            return (int)$a->getAttribute('order') <=> (int)$b->getAttribute('order');
        });
        return $instructionsArray;
    }
}