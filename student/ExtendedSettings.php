<?php
/**
 * IPP Interpreter
 * Class for handling extended settings
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student;

use IPP\Core\Settings as BaseSettings;
use IPP\Core\Exception\ParameterException;
use IPP\Core\ReturnCode;

class ExtendedSettings extends BaseSettings {
    public string $statsFile = "";
    public bool $hot   = false;
    public bool $eol   = false;
    public bool $vars  = false;
    public bool $stats = false;
    public bool $stack = false;
    public bool $countInstructions = false;


    /**
     * @var array<string>
     */
    public array $statParams = [];

    public function processArgs(): void {
        parent::processArgs();  // Call the original method to handle existing parameters

        // Get the arguments
        $args = $_SERVER['argv'];
        $extendedOptions = getopt("", ['insts', 'hot', 'vars', 'stack', 'print:', 'eol']);

        // set recognized parameters to be processed
        $recognizedParams = ['--insts', '--hot', '--vars', '--stack', '--print=', '--eol'];

        // Process new parameters
        foreach ($args as $arg) {
            if (strpos($arg, '--stats=') === 0) {
                $this->statsFile = substr($arg, strlen('--stats='));
                $this->stats = true;
            } elseif (in_array($arg, $recognizedParams) || strpos($arg, '--print=') === 0) {
                $this->statParams[] = $arg;
            }
        }

        // set the values of the new parameters for further processing
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
        if (isset($extendedOptions['eol'])) {
            $this->eol = true;
        }

        // Validate that --stats is present if any stats-related options are set
        if (!$this->stats && ($this->countInstructions || $this->hot || $this->vars || $this->stack)) {
            HelperFunctions::handleException(ReturnCode::PARAMETER_ERROR);
        }
    }
}
