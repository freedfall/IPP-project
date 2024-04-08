<?php

namespace IPP\Student;

use DOMDocument;
use DOMXPath;
use Exception;

class XMLSourceAnalyzer
{
    protected $dom;

    public function __construct(DOMDocument $dom)
    {
        $this->dom = $dom;
    }

    public function analyze(): void
    {
        // check root element
        $this->checkRoot();

        // check instructions
        $this->checkInstructions();
    }

    protected function checkRoot(): void
    {
        // check that root element is 'program'
        $root = $this->dom->documentElement;
        if ($root === null || $root->nodeName !== 'program') { 
            throw new Exception("Root element 'program' is missing or incorrect.");
        }

        // check that 'language' attribute is 'IPPcode24'
        $language = $root->getAttribute('language');
        if (strtolower($language) !== 'ippcode24') {
            throw new Exception("The 'language' attribute of the program is missing or not 'IPPcode24'.");
        }
    }

    protected function checkInstructions(): void
    {
        $xpath = new DOMXPath($this->dom);
        $instructions = $xpath->query('/program/instruction');

        // check that there is at least one instruction
        if ($instructions === false || $instructions->length === 0) {
            throw new Exception("No instructions found in the program.");
        }

        // check that each instruction has 'order' and 'opcode' attributes
        foreach ($instructions as $instruction) {
            if (!$instruction->hasAttribute('order') || !$instruction->hasAttribute('opcode')) {
                throw new Exception("Each instruction must have both 'order' and 'opcode' attributes.");
            }

            // check that 'order' attribute is a positive integer
            $order = $instruction->getAttribute('order');
            if (!filter_var($order, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                throw new Exception("The 'order' attribute of an instruction must be a positive integer.");
            }
        }
    }
}

