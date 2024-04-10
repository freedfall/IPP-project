<?php
/**
 * IPP Interpreter
 * Class for outputting results
 * @author Timur Kininbayev (xkinin00)
 * 
 */
namespace IPP\Student;

use IPP\Core\StreamWriter;
use IPP\Student\HelperFunctions;

class ResultOutputter
{
    protected StreamWriter $stdout;
    protected StreamWriter $stderr;

    /**
     * @param StreamWriter $stdoutWritter Standard output stream
     */
    public function __construct(StreamWriter $stdoutWriter, StreamWriter $stderrWriter)
    {
        $this->stdout = $stdoutWriter;
        $this->stderr = $stderrWriter;
    }

    public function outputResult(mixed $result): void
    {
        // Output result
        $result_type = HelperFunctions::getDataType($result);
        // print($result);
        // print("\n");
        // print($result_type);
        // print("\n");
            switch ($result_type){
                case 'bool':
                    $this->stdout->writeBool($result);
                    break;
                case 'int':
                    $this->stdout->writeInt((int)$result);
                    break;
                case 'nil':
                    $this->stdout->writeString("");
                    break;
                case 'float':
                    $this->stdout->writeFloat((float)$result);
                    break;
                default:
                    $this->stdout->writeString($result);
            }
    }
    public function outputError(mixed $result): void
    {
        // Output result
        $result_type = HelperFunctions::getDataType($result);
            switch ($result_type){
                case 'bool':
                    $this->stdout->writeBool($result);
                    break;
                case 'int':
                    $this->stdout->writeInt((int)$result);
                    break;
                case 'nil':
                    $this->stdout->writeString("");
                    break;
                case 'float':
                    $this->stdout->writeFloat((float)$result);
                    break;
                default:
                    $this->stdout->writeString($result);
            }
    }
}
