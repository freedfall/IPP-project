<?php
/**
 * IPP Interpreter
 * Class for analyzing XML source
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use DOMDocument;
use DOMXPath;
use Exception;
use IPP\Core\ReturnCode;

class XMLAnalyzer
{
    protected DOMDocument $dom;

    public function __construct(DOMDocument $dom)
    {
        $this->dom = $dom;
    }

    /**
     * Analyze the XML source
     * @return array<mixed> Array of instructions
     */
    public function analyze(): array
    {
        $this->checkRoot();
        $instructionsData = $this->extractInstructions();

        return $instructionsData;
    }

    /**
     * Check the root element of the XML source
     * @throws Exception If the root element is missing or incorrect
     */
    protected function checkRoot(): void
    {
        // check that root element is 'program'
        $root = $this->dom->documentElement;
        if ($root === null || $root->nodeName !== 'program') { 
            HelperFunctions::handleException(ReturnCode::INPUT_FILE_ERROR);
        }

        // check that 'language' attribute is 'IPPcode24'
        $language = $root->getAttribute('language');
        if (strtolower($language) !== 'ippcode24') {
            HelperFunctions::handleException(ReturnCode::INPUT_FILE_ERROR);
        }
    }

    /**
     * Check the instructions in the XML source
     * @throws Exception If no instructions are found or if an instruction is missing 'order' or 'opcode' attributes
     */
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
            if ($instruction instanceof \DOMElement) {
                if (!$instruction->hasAttribute('order') || !$instruction->hasAttribute('opcode')) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
        
                // check that 'order' attribute is a positive integer
                $order = $instruction->getAttribute('order');
                if (!filter_var($order, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
            } else {
                HelperFunctions::handleException(ReturnCode::INPUT_FILE_ERROR);
            }
        }
    }

    /**
     * Extract instructions from the XML source
     * @return array<mixed> Array of instructions
     */
    protected function extractInstructions(): array
    {
        $xpath = new DOMXPath($this->dom);
        $instructionsNodes = $xpath->query('/program/instruction');
        $instructionsData = [];

        foreach ($instructionsNodes as $node) {
            if ($node instanceof \DOMElement) {
                $opcode = $node->getAttribute('opcode');
                $order = $node->getAttribute('order');
                $args = $this->extractArgs($node);
    
                $instructionsData[] = [
                    'order' => $order,
                    'opcode' => $opcode,
                    'args' => $args,
                ];
            }
        }

        return $instructionsData;
    }

    /**
     * Extract arguments from an instruction node
     * @param \DOMNode $instruction Instruction node
     * @return array<mixed> Array of arguments
     */
    protected function extractArgs(\DOMNode $instruction): array
    {
        $args = [];
        foreach ($instruction->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $argType = $child->getAttribute('type');
                $value = $child->nodeValue;
                $args[] = [
                    'type' => $child->nodeName, // arg1, arg2, etc.
                    'value' => $value,
                    'dataType' => $argType,
                ];
            }
        }
        return $args;
    }
}

