# Index File
Read `index.txt` to identify all symbols and their assocated file locations.

For best results do not parse `index.txt` in its entirety and instead use `grep -n SYMBOL index.txt` to identify the specific symbol you're looking for and
be sure to escape any namespaces with forward slash (`/`) so searching for `\Namespace\ClassName` would be `grep -n /\Namespace/\ClassName index.txt`.

## Index Format
The index file uses a simple format of SYMBOL:PATH where SYMBOL is the name of a function, constant, interface, class or trait and path is the file path.

### Example
The following is an example of how a line in the index file is formatted

\Namespace\ClassName:index/src/Project/Namespace/ClassName.php.txt

Information on the Symbol `\Namespace\ClassName` is located in the file `index/src/Project/Namespace/ClassName.php.txt`

# File Contents
Each file contains multiple lines with a line number or range of line numbers paired with symbols seperated by a colon (:). 
A single line number usually indicates a constants or properties while a range indicates a function, interface, class, method or trait.

## Example
The following is an example of how a file is formatted where the square brackets represent the filename (square brackets are not present in the file).

[index/src/Project/Namespace/ClassName.php.txt]
1-10:\Namespace\ClassName
2:\Namespace\ClassName::CONSTANT
3:\Namespace\ClassName::$Property
4-6:\Namespace\ClassName::__construct()
7-10:\Namespace\ClassName::MethodName()

The class name in this example is `ClassName` within namespace `Namespace` and ranges from lines 1 to 10.
Line 2 contains a constant with the name `CONSTANT`
Line 3 contains a property of `ClassName` with the name `Property`
Line 4 to 6 contains the constructor for `ClassName`
Line 7 to 10 contains a method for `ClassName` named `MethodName`