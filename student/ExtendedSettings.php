<?php
/**
 * IPP Interpreter
 * Class for handling extended settings
 * @author Timur Kininbayev (xkinin00)
 * 
 */

namespace IPP\Student;

use IPP\Core\Settings as BaseSettings;
use IPP\Core\Exception\ParameterException;

class ExtendedSettings extends BaseSettings {
    protected bool $stats = false;
    protected string $statsFile = "";
    protected bool $countInstructions = false;
    protected bool $hot = false;
    protected bool $vars = false;
    protected bool $stack = false;
    protected string $printString = "";
    protected bool $eol = false;

    public function processArgs(): void {
        parent::processArgs();  // Call the original method to handle existing parameters

        $extendedOptions = getopt("", [
            "stats:",
            "insts",
            "hot",
            "vars",
            "stack",
            "print:",
            "eol"
        ]);

        // Process new parameters
        if (isset($extendedOptions['stats'])) {
            $this->stats = true;
            $this->statsFile = $extendedOptions['stats'];
        }
        if (isset($extendedOptions['insts'])) {
            $this->countInstructions = true;
        }
        if (isset($extendedOptions['hot'])) {
            $this->hot = true;
        }
        if (isset($extendedOptions['vars'])) {
            $this->vars = true;
        }
        if (isset($extendedOptions['stack'])) {
            $this->stack = true;
        }
        if (isset($extendedOptions['print'])) {
            $this->printString = $extendedOptions['print'];
        }
        if (isset($extendedOptions['eol'])) {
            $this->eol = true;
        }

        // Validate that --stats is present if any stats-related options are set
        if (!$this->stats && ($this->countInstructions || $this->hot || $this->vars || $this->stack || $this->printString !== "")) {
            throw new ParameterException("Missing --stats parameter with statistics-related options.");
        }
    }
}
