# Project of IPP subject, VUT FIT 2024

## Purpose is to Design, implement and document two programs for the analysis and interpretation of unstructured imperative language IPPcode24

### Project is divided in 2 parts

* [Parser written in Python](#parser)
* [Interpret written in PHP 8.3](#interpret)

## **Parser**

### First part of project - Python script dedicated to processing IPPcode24 source code, verifying its lexical and syntactic correctness, and converting it into a structured XML representation.

### Outline

* [Design Philosophy](#design-philosophy)
* [Solution process](#solution-process)
* [Internal Representation](#internal-representation)
  * [Lexical Analysis](#lexical-analysis)
  * [Syntactic Analysis](#syntactic-analysis)
  * [XML generation](#xml-generation)
* [Evaluation](#evaluation)

### Design Philosophy

The script's design adheres to principles of modularity, readability, and robust error handling. Each component of the script - *argument parsing*, *input reading*, *lexical analysis*, *syntactic analysis*, *and XML generation* - is encapsulated in dedicated functions.

### Solution process

The script begins with parsing command-line arguments, specifically looking for a `--help` option to display usage information. It then reads the IPPcode24 source code, ensuring the presence of a correct header. Following this, it performs lexical analysis to tokenize the input and syntactic analysis to validate the structure of each instruction according to predefined rules. Finally, it generates an XML representation of the program, adhering to the specified format. Special attention is given to error handling, with detailed error messages guiding the correction of syntactic and lexical mistakes.

### Internal Representation

Internally, the script utilizes dictionaries, tuples, and lists to manage the program's structure and its elements efficiently. Instructions and their expected operands are mapped in a dictionary (instruction_rules), facilitating quick validation checks during the syntactic analysis phase.

#### Lexical Analysis

* Lexical analysis is implemented using regular expressions for tokenization. Tokens extracted from the source code are stored as tuples containing their value, type for compression, and actual type for containing all the information needed in syntactic analysis and XML generation.

#### Syntactic analysis

* Syntactic analysis verifies the structure of each instruction against predefined rules, ensuring each opcode is followed by the correct number and type of operands. This phase utilizes the instruction_rules dictionary, which maps each opcode to its expected operand types.
* The analysis iterates over tokens identified during lexical analysis, checking each instruction's opcode and operands. A key aspect of this phase is the introduction of the "SYMB" type, which encompasses various operand types (variables, constants) not explicitly categorized during lexical analysis.
* For each instruction, the syntactic analysis:
  * Confirms the first token as a valid opcode.
  * Matches the number of operands to the expected count for that opcode.
  * Validates each operand's type against the expected types, incorporating special handling for the "SYMB" type.

#### XML generation

* The XML generation process converts validated IPPcode24 instructions into an XML document using the ElementTree API. This structured representation begins with a `program` root element, indicating the source language as IPPcode24.
* For each instruction, an `instruction` element is created, featuring order and opcode attributes to reflect its sequence and type. Operand elements (`arg1`, `arg2`, etc.) are nested within their respective instruction elements, each marked with a type attribute (e.g., "var", "int", "bool", "string") and populated with the operand's value, adhering to XML standards.

#### Evaluation

    6/7

## Interpret

### This part of the project involves the implementation and documentation of an interpreter for the unstructured imperative language IPPcode24, using PHP 8.3. The interpreter reads an XML representation of IPPcode24 instructions and executes them sequentially, maintaining runtime environments such as variable frames and a data stack.

### Outline

* [Structure](#structure)
* [Detailed components](#detailed-components)
  * [Interpreter](#interpreter)
  * [XML Analyzer](#xml-analyzer)
  * [Instruction Sorter](#instruction-sorter)
  * [Instruction Processor](#instruction-processor)
  * [Statistics Collector](#statistics-collector)
* [Execution Process](#execution-process)
* [Error Handling](#error-handling)

### Structure

The interpreter is designed around several key classes and components that handle different aspects of the execution process:

![class diagram](student/Interpret%20Diagram.png)

  * **Interpreter** - The main class that orchestrates the parsing of input XML, instruction execution, and output handling.
  * **InstructionProcessor** - Processes individual instructions according to their opcodes and arguments.
  * **XMLAnalyzer** - Parses the input XML file and validates its structure and content.
  * **InstructionSorter** - Orders instructions based on their 'order' attribute before execution.
  * **ExtendedSettings** - Manages command-line arguments and initializes I/O settings.
  * **StatisticsCollector** - Collects and outputs statistics about the program execution based on user-specified flags.

### Detailed components

#### **Interpreter**

  * Responsible for setting up the environment, including source and input readers.
  * Utilizes `XMLAnalyzer` to convert XML input into a structured list of instructions.
  * Uses `InstructionSorter` to ensure the instructions are processed in the correct order.
  * Manages execution flow through `InstructionProcessor`.
  
#### **XML Analyzer**

  * Ensures the XML structure is correct, checking elements like the root node and language attributes.
  * Validates instructions and their arguments to ensure they adhere to IPPcode24 specifications.
  * Extracts instructions (opcodes and orders) and their arguments for further processing.

#### **Instruction Sorter**

  * Sorts instructions based on their 'order' attribute to facilitate sequential processing.

#### **Instruction Processor**

  * Main class containing methods for interpreting every instruction
  * Executes instructions based on opcode.
  * Manages runtime state, including variable frames (global, local, and temporary) and the data stack.
  * Handles special operations like function calls and returns.

#### **Statistics Collector**

  * Usage:

    --stats=filename.txt [--insts] [--hot] [--vars] [--stack] [--print="string"] [--eol]

  * Optionally activated by command-line arguments to track various metrics:
    * Total number of instructions executed `--insts`.
    * Most frequently executed instruction `--hot` (outputs first order of this instruction)
    * Maximum number of simultaneously initialized variables `--vars`
    * Maximum stack size `--stack`
  * Outputs collected statistics to a specified file line by line in the given order (arguments can repeat)

### Execution Process

1. **Initialization**
  * The `Interpreter` initializes settings and prepares the environment based on command-line arguments.
2. **XML parsing** 
  * `XMLAnalyzer` reads and validates the XML file, extracting structured instruction data.
3. **Instruction Sorting**
  * `InstructionSorter` arranges the instructions in the correct execution order.
4. **Execution**
  * `Interpreter` iteratively processes each instruction using `InstructionProcessor`, managing program state and variable scopes.
5. **Statistics Collection**
  * If enabled,`StatisticsCollector` tracks and records execution metrics, writing to a file upon completion.

### Error Handling

Error handling is implemented in class `HelperFunctions`, managing every return code with proper exit, writing error message to STDERR

### Interpreter Evaluation

  will be updated
