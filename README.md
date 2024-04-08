# Project of IPP subject, VUT FIT 2024

## Purpose is to Design, implement and document two programs for the analysis and interpretation of unstructured imperative language IPPcode24

### Project is divided in 2 parts

* [Parser written in Python](#parser)
* [Interpret written in PHP 8.3](#interpret)

<a id="parser"></a>
## **Parser**

### First part of project - Python script dedicated to processing IPPcode24 source code, verifying its lexical and syntactic correctness, and converting it into a structured XML representation.

### Outline

* [Design Philosophy](#design)
* [Solution process](#solution)
* [Internal Representation](#internal)
  * [Lexical Analysis](#lexical)
  * [Syntactic Analysis](#syntax)
  * [XML generation](#generation)
* [Evaluation](#eval)

<a id="design"></a>
### Design Philosophy

The script's design adheres to principles of modularity, readability, and robust error handling. Each component of the script - *argument parsing*, *input reading*, *lexical analysis*, *syntactic analysis*, *and XML generation* - is encapsulated in dedicated functions.

<a id="solution"></a>
### Solution process

The script begins with parsing command-line arguments, specifically looking for a `--help` option to display usage information. It then reads the IPPcode24 source code, ensuring the presence of a correct header. Following this, it performs lexical analysis to tokenize the input and syntactic analysis to validate the structure of each instruction according to predefined rules. Finally, it generates an XML representation of the program, adhering to the specified format. Special attention is given to error handling, with detailed error messages guiding the correction of syntactic and lexical mistakes.

<a id="internal"></a>
### Internal Representation

Internally, the script utilizes dictionaries, tuples, and lists to manage the program's structure and its elements efficiently. Instructions and their expected operands are mapped in a dictionary (instruction_rules), facilitating quick validation checks during the syntactic analysis phase.

<a id="lexical"></a>
#### Lexical Analysis

* Lexical analysis is implemented using regular expressions for tokenization. Tokens extracted from the source code are stored as tuples containing their value, type for compression, and actual type for containing all the information needed in syntactic analysis and XML generation.

<a id="syntax"></a>
#### Syntactic analysis

* Syntactic analysis verifies the structure of each instruction against predefined rules, ensuring each opcode is followed by the correct number and type of operands. This phase utilizes the instruction_rules dictionary, which maps each opcode to its expected operand types.
* The analysis iterates over tokens identified during lexical analysis, checking each instruction's opcode and operands. A key aspect of this phase is the introduction of the "SYMB" type, which encompasses various operand types (variables, constants) not explicitly categorized during lexical analysis.
* For each instruction, the syntactic analysis:
  * Confirms the first token as a valid opcode.
  * Matches the number of operands to the expected count for that opcode.
  * Validates each operand's type against the expected types, incorporating special handling for the "SYMB" type.

<a id="generation"></a>
#### XML generation

* The XML generation process converts validated IPPcode24 instructions into an XML document using the ElementTree API. This structured representation begins with a `program` root element, indicating the source language as IPPcode24.
* For each instruction, an `instruction` element is created, featuring order and opcode attributes to reflect its sequence and type. Operand elements (`arg1`, `arg2`, etc.) are nested within their respective instruction elements, each marked with a type attribute (e.g., "var", "int", "bool", "string") and populated with the operand's value, adhering to XML standards.

<a id="eval"></a>
#### Evaluation

    6/7

<a id="interpret"></a>
## Interpret
