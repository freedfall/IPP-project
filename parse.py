#########################################################
### Skript pro zpracování zdrojového kódu v IPPcode24 ###
###       Autor: Kininbayev Timur (xkinin00)          ###
#########################################################
import argparse
import re
import sys
from xml.etree.ElementTree import Element, SubElement, tostring
from xml.dom.minidom import parseString

# Function for processing command line arguments
def parse_arguments():
    parser = argparse.ArgumentParser(add_help=False)
    parser.add_argument('--help', action='store_true', help='Show this help message and exit')
    args = parser.parse_args()
    if args.help:
        print("Usage: python parse.py [--help]")
        print("Reads from standard input the source code in IPPcode24, checks the code for lexical and syntactic correctness, and prints it to the standard XML representation.")
        sys.exit(0)

#Function for reading source code from standard input
def read_source_code():
    lines = []
    for line in sys.stdin:
        line = line.strip()
        if not line or line.startswith('#'):  # skip empty lines and comments
            continue
        lines.append(line)
    return lines

# Function for checking the header of the source code
def check_header(lines):
    if not lines or not lines[0].lower().startswith('.ippcode24'):
        return False
    return True

# Function for lexical analysis of the code
def lexical_analysis(line):
    tokens = re.split(r'\s+', line.split('#')[0].strip())
    token_details = []

    for token in tokens:
        if token.upper() in ["CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK",
                             "DEFVAR", "POPS", "CALL", "LABEL", "JUMP", "PUSHS", "WRITE",
                             "EXIT", "DPRINT", "MOVE", "STRLEN", "TYPE", "NOT", "READ",
                             "ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR",
                             "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR", "JUMPIFEQ",
                             "JUMPIFNEQ", "INT2FLOAT", "INT2CHAR", "FLOAT2INT"]:
            token_type = "OPCODE"
        elif re.match(r'^(GF|LF|TF)@[A-Za-z0-9_\-$&%*!?]+$', token):
            token_type = "VARIABLE"
        elif re.match(r'^int@[-+]?\d+$', token):
            token_type = "CONSTANT"
            token_actual_type = "int"
        elif re.match(r'^bool@(true|false)$', token):
            token_type = "CONSTANT"
            token_actual_type = "bool"    
        elif re.match(r'^string@.*$', token):
            token_type = "CONSTANT"
            token_actual_type = "string"
        elif token == "nil@nil":
            token_type = "CONSTANT"
            token_actual_type = "nil"
        elif token in ["int", "bool", "string"]:
            token_type = "TYPE"
        elif re.match(r'^[A-Za-z0-9_\-$&%*!?]+$', token):
            token_type = "LABEL"
        else:
            token_type = "UNKNOWN"
        token_details.append((token, token_type, token_actual_type if token_type == "CONSTANT" else None))
    return token_details

# Instructions and their operands
instruction_rules = {
    "CREATEFRAME": [],
    "PUSHFRAME": [],
    "POPFRAME": [],
    "RETURN": [],
    "BREAK": [],
    "DEFVAR": ["VARIABLE"],
    "POPS": ["VARIABLE"],
    "CALL": ["LABEL"],
    "LABEL": ["LABEL"],
    "JUMP": ["LABEL"],
    "PUSHS": ["SYMB"],
    "WRITE": ["SYMB"],
    "EXIT": ["SYMB"],
    "DPRINT": ["SYMB"],
    "MOVE": ["VARIABLE","SYMB"],
    "STRLEN": ["VARIABLE", "SYMB"],
    "TYPE": ["VARIABLE", "SYMB"],
    "NOT": ["VARIABLE", "SYMB"],
    "READ": ["VARIABLE", "TYPE"],
    "ADD": ["VARIABLE", "SYMB", "SYMB"],
    "SUB": ["VARIABLE", "SYMB", "SYMB"],
    "MUL": ["VARIABLE", "SYMB", "SYMB"],
    "IDIV": ["VARIABLE", "SYMB", "SYMB"],
    "LT": ["VARIABLE", "SYMB", "SYMB"],
    "GT": ["VARIABLE", "SYMB", "SYMB"],
    "EQ": ["VARIABLE", "SYMB", "SYMB"],
    "AND": ["VARIABLE", "SYMB", "SYMB"],
    "OR": ["VARIABLE", "SYMB", "SYMB"],
    "STRI2INT": ["VARIABLE", "SYMB", "SYMB"],
    "CONCAT": ["VARIABLE", "SYMB", "SYMB"],
    "GETCHAR": ["VARIABLE", "SYMB", "SYMB"],
    "SETCHAR": ["VARIABLE", "SYMB", "SYMB"],
    "JUMPIFEQ": ["LABEL", "SYMB", "SYMB"],
    "JUMPIFNEQ": ["LABEL", "SYMB", "SYMB"],
    "INT2FLOAT": ["VARIABLE", "SYMB"],
    "INT2CHAR": ["VARIABLE", "SYMB"],
    "FLOAT2INT": ["VARIABLE", "SYMB"],
}

# Function for syntactic analysis of the list of tokens and checking compliance with the rules
def syntactic_analysis(token_details):
    if token_details[0][1] != "OPCODE":
        return False, "First token must be an OPCODE"
    opcode = token_details[0][0].upper() # get the opcode
    operands = token_details[1:] # get the operands

    expected_operands = instruction_rules.get(opcode, []) # get the expected operands for the opcode

    # check if the number of operands is correct
    if len(operands) != len(expected_operands):
        return False, f"Incorrect number of operands for {opcode}. Expected {len(expected_operands)}, got {len(operands)}"

    # check if the type of each operand is correct
    for idx, (operand_type, expected_type) in enumerate(zip(operands, expected_operands)):
        if expected_type == "SYMB" and operand_type[1] in ["VARIABLE", "CONSTANT", "string", "bool", "int", "nil"]:
            continue  # argument type is correct
        elif expected_type == operand_type[1]:
            continue 
        else:
            return False, f"Opcode {opcode}: Operand {idx + 1} expected type {expected_type}, got {operand_type[1]}"
    return True, "Syntax is correct"

# Function for generating the XML representation of the program
def generate_xml(instructions):
    root = Element('program', language='IPPcode24')
    order = 1  # start with order 1

    for tokens in instructions:
        syntax_check, message = syntactic_analysis(tokens)
        if not syntax_check:
            print(f"Syntax error: {message}", file=sys.stderr)
            sys.exit(23)
            
        opcode = tokens[0][0].upper()  # get the opcode
        operands = tokens[1:] 

        # create an instruction element in XML
        instr_elem = SubElement(root, 'instruction', order=str(order), opcode=opcode)
        order += 1 

        # add instruction arguments
        for idx, (value, type, actual_type) in enumerate(operands, start=1):
            if actual_type is not None:
                arg_elem = SubElement(instr_elem, f'arg{idx}', type=actual_type)
            else:
                if type == "VARIABLE":
                    arg_elem = SubElement(instr_elem, f'arg{idx}', type="var")
                else:
                    arg_elem = SubElement(instr_elem, f'arg{idx}', type=type.lower()) 
            if type == "VARIABLE":
                arg_elem.text = value
            else:
                arg_elem.text = value.split('@')[1] if '@' in value else value
    root.tail = "\n"

    # generate and return pretty formatted XML
    xml_str = tostring(root, 'utf-8')
    pretty_xml = parseString(xml_str).toprettyxml(indent="    ", encoding="UTF-8")
    return pretty_xml.decode()


# main function of the program
def main():
    parse_arguments()
    lines = read_source_code()
    if not check_header(lines):
        print("Error: Missing or incorrect header.", file=sys.stderr)
        sys.exit(21)
    instructions = []  # list of instructions

    for line in lines[1:]:  # skip the header
        token_details = lexical_analysis(line)
        syntax_check, message = syntactic_analysis(token_details)
        if syntax_check:
            instructions.append(token_details)  # add the token details to the list
        else:
            print(f"Syntax error: {message}", file=sys.stderr)
            sys.exit(23)
    xml_output = generate_xml(instructions)
    print(xml_output)

main()