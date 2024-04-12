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
        $this->dom->preserveWhiteSpace = false;
        // check if the XML source is well-formed
        // if (!$this->dom->validate()) {
        //     HelperFunctions::handleException(ReturnCode::INVALID_XML_ERROR);
        // }
    }

    /**
     * Analyze the XML source
     * @return array<mixed> Array of instructions
     */
    public function analyze(): array
    {
        $this->checkRoot();
        $this->checkForInvalidElements();
        $this->checkInstructions();
        $instructionsData = $this->extractInstructions();

        return $instructionsData;
    }

    protected function checkForInvalidElements(): void {
        $xpath = new DOMXPath($this->dom);
        $invalidElements = $xpath->query('/program/*[not(self::instruction)]');
    
        if ($invalidElements->length > 0) {
            HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
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
            HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }

        // check that 'language' attribute is 'IPPcode24'
        $language = $root->getAttribute('language');
        if (strtolower($language) !== 'ippcode24') {
            HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
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
        if ($instructions === false) {
            HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
        }
        // print_r($instructions);
        $seenOrders = [];
        // check that each instruction has 'order' and 'opcode' attributes
        foreach ($instructions as $instruction) {
            if ($instruction instanceof \DOMElement) {
                if (!$instruction->hasAttribute('order') || !$instruction->hasAttribute('opcode')) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                // check that 'order' attribute is a positive integer
                $order = trim($instruction->getAttribute('order'));
                if (!filter_var($order, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                // check that 'order' attribute is unique
                if (isset($seenOrders[$order])) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                $seenOrders[$order] = true;

                // check that 'opcode' attribute is a non-empty string
                $opcode = $instruction->getAttribute('opcode');
                if (empty($opcode)) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
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
                $order = trim($node->getAttribute('order'));
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
                //check if arg has type attribute
                if (empty($argType)) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                //check so args names are okay (arg1, arg2, etc.)
                if (!preg_match('/^arg([1-9][0-9]*)$/', $child->nodeName, $matches)) {
                    HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                $argNumber = (int)$matches[1]-1; // Получение номера аргумента и корректировка для использования в качестве ключа массива
                $value = trim($child->nodeValue);
                $args[$argNumber] = [
                    'type' => $child->nodeName,
                    'value' => $value,
                    'dataType' => $argType,
                ];
            }
        }
        ksort($args); // Сортировка массива аргументов по ключам
        $expectedArgIndex = 0;
        foreach ($args as $index => $arg) {
            if ($index != $expectedArgIndex) {
                HelperFunctions::handleException(ReturnCode::INVALID_SOURCE_STRUCTURE);
            }
            $expectedArgIndex++;
        }
        return array_values($args);
    }
}

