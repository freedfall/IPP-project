<?php
/**
 * IPP Interpreter
 * Class for outputting results
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

class ResultOutputter
{
    protected $stdout;
    protected $stderr;

    public function __construct($stdout, $stderr)
    {
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function outputResult(string $result): void
    {
        // Output result
        $this->stdout = $result;
    }

    public function outputError(string $error): void
    {
        // Output error
    }
}
