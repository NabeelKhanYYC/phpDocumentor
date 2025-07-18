# Index File
Read files.txt to identify all files available for processing in sub-folders.

# File Contents
Each file contains multiple lines with a line number or range of line numbers paired with symbols seperated by a colon (:). 
A single line number usually indicates a constants or properties while a range indicates a function, interface, class, method or trait.

## Example
The following is an example of how a file is formatted where the square brackets represent the filename.

The class name in this example is 'ClassName' within namespace 'Namespace' and ranges from lines 1 to 10.
Line 2 contains a constant with the name 'CONSTANT'
Line 3 contains a property of 'ClassName' with the name 'Property'
Line 4 to 6 contains the constructor for 'ClassName'
Line 7 to 10 contains a method for 'ClassName' named 'MethodName'

[./src/Project/Namespace/ClassName.txt]
1-10:\Namespace\ClassName
2:\Namespace\ClassName::CONSTANT
3:\Namespace\ClassName::$Property
4-6:\Namespace\ClassName::__construct()
7-10:\Namespace\ClassName::MethodName()