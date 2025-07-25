<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace phpDocumentor\Descriptor\Builder;

use phpDocumentor\Descriptor\Builder\Reflector\ArgumentAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\ClassAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\ConstantAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Docblock\DescriptionAssemblerReducer;
use phpDocumentor\Descriptor\Builder\Reflector\EnumAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\EnumCaseAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\FileAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\FunctionAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\InterfaceAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\MethodAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\NamespaceAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\PropertyAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\PropertyHookAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Reducer\AttributeReducer;
use phpDocumentor\Descriptor\Builder\Reflector\Reducer\MetadataReducer;
use phpDocumentor\Descriptor\Builder\Reflector\Reducer\VisibilityReducer;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\AuthorAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\DeprecatedAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\ExampleAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\GenericTagAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\InvalidTagAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\LinkAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\MethodAssembler as MethodTagAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\ParamAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\PropertyAssembler as PropertyTagAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\ReturnAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\SeeAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\SinceAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\ThrowsAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\UsesAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\VarAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\Tags\VersionAssembler;
use phpDocumentor\Descriptor\Builder\Reflector\TraitAssembler;
use phpDocumentor\Descriptor\Descriptor;
use phpDocumentor\Reflection\DocBlock\ExampleFinder;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Example;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlock\Tags\Version;
use phpDocumentor\Reflection\Php\Argument;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Constant;
use phpDocumentor\Reflection\Php\Enum_;
use phpDocumentor\Reflection\Php\EnumCase;
use phpDocumentor\Reflection\Php\File;
use phpDocumentor\Reflection\Php\Function_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\Property;
use phpDocumentor\Reflection\Php\PropertyHook;
use phpDocumentor\Reflection\Php\Trait_;

use function array_merge;

/**
 * Attempts to retrieve an Assembler for the provided criteria.
 */
class AssemblerFactory
{
    /** @var AssemblerMatcher[] */
    protected $assemblers = [];

    /** @var AssemblerMatcher[] */
    protected $fallbackAssemblers = [];

    /**
     * Registers an assembler instance to this factory.
     *
     * @param Matcher<object> $matcher   A callback function accepting the criteria as only parameter and which must
     *       return a boolean.
     * @param AssemblerInterface<Descriptor, object> $assembler An instance of the Assembler that
     *     will be returned if the callback returns true with the provided criteria.
     */
    public function register(Matcher $matcher, AssemblerInterface $assembler): void
    {
        $this->assemblers[] = new AssemblerMatcher($matcher, $assembler);
    }

    /**
     * Registers an assembler instance to this factory that is to be executed after all other assemblers have been
     * checked.
     *
     * @param Matcher<object> $matcher   A callback function accepting the criteria as only parameter and which must
     *       return a boolean.
     * @param AssemblerInterface<Descriptor, object> $assembler An instance of the Assembler that
     *     will be returned if the callback returns true with the provided criteria.
     */
    public function registerFallback(Matcher $matcher, AssemblerInterface $assembler): void
    {
        $this->fallbackAssemblers[] = new AssemblerMatcher($matcher, $assembler);
    }

    /**
     * Retrieves a matching Assembler based on the provided criteria or null if none was found.
     *
     * @param TParamInput $criteria
     * @param class-string<TParamDescriptor> $type
     *
     * @return AssemblerInterface<TParamDescriptor, TParamInput>|null
     *
     * @psalm-template TParamInput of object
     * @psalm-template TParamDescriptor of Descriptor
     */
    public function get(object $criteria, string $type): AssemblerInterface|null
    {
        foreach (array_merge($this->assemblers, $this->fallbackAssemblers) as $candidate) {
            if ($candidate->match($criteria)) {
                /**
                 * @var AssemblerInterface<TParamDescriptor, TParamInput> This is cheating, but there's no way to make
                 * psalm understand that TParamDescriptor & TParamInput given in param happen to be the same as the one
                 * in the factory. We know it is because they matched.
                 */
                return $candidate->getAssembler();
            }
        }

        return null;
    }

    public static function createDefault(ExampleFinder $exampleFinder): self
    {
        $factory = new self();

        $descriptionReducer = new DescriptionAssemblerReducer();
        $metadataReducer = new MetadataReducer();
        $attributeReducer = new AttributeReducer();
        $visibilityReducer = new VisibilityReducer();

        $argumentAssembler = new ArgumentAssembler();

        $factory->register(Matcher::forType(File::class), new FileAssembler($metadataReducer));
        $factory->register(
            Matcher::forType(Constant::class),
            new ConstantAssembler($attributeReducer, $visibilityReducer),
        );
        $factory->register(Matcher::forType(Trait_::class), new TraitAssembler($attributeReducer));
        $factory->register(Matcher::forType(Class_::class), new ClassAssembler($attributeReducer));
        $factory->register(Matcher::forType(Enum_::class), new EnumAssembler($attributeReducer));
        $factory->register(Matcher::forType(EnumCase::class), new EnumCaseAssembler($attributeReducer));
        $factory->register(Matcher::forType(Interface_::class), new InterfaceAssembler($attributeReducer));
        $factory->register(
            Matcher::forType(Property::class),
            new PropertyAssembler($attributeReducer, $visibilityReducer),
        );
        $factory->register(
            Matcher::forType(PropertyHook::class),
            new PropertyHookAssembler($argumentAssembler, $attributeReducer, $metadataReducer),
        );
        $factory->register(Matcher::forType(Argument::class), $argumentAssembler);
        $factory->register(Matcher::forType(Method::class), new MethodAssembler(
            $argumentAssembler,
            $attributeReducer,
            $visibilityReducer,
        ));
        $factory->register(Matcher::forType(Function_::class), new FunctionAssembler(
            $argumentAssembler,
            $attributeReducer,
        ));
        $factory->register(Matcher::forType(Namespace_::class), new NamespaceAssembler());

        $factory->register(Matcher::forType(Author::class), new AuthorAssembler());
        $factory->register(Matcher::forType(Deprecated::class), new DeprecatedAssembler());
        $factory->register(Matcher::forType(Example::class), new ExampleAssembler($exampleFinder));
        $factory->register(Matcher::forType(Link::class), new LinkAssembler($descriptionReducer));
        $factory->register(Matcher::forType(Tags\Method::class), new MethodTagAssembler());
        $factory->register(Matcher::forType(Tags\Property::class), new PropertyTagAssembler());
        $factory->register(Matcher::forType(Tags\PropertyRead::class), new PropertyTagAssembler());
        $factory->register(Matcher::forType(Tags\PropertyWrite::class), new PropertyTagAssembler());
        $factory->register(Matcher::forType(Tags\InvalidTag::class), new InvalidTagAssembler());
        $factory->register(Matcher::forType(Var_::class), new VarAssembler());
        $factory->register(Matcher::forType(Param::class), new ParamAssembler());
        $factory->register(Matcher::forType(Throws::class), new ThrowsAssembler());
        $factory->register(Matcher::forType(Return_::class), new ReturnAssembler());
        $factory->register(Matcher::forType(Uses::class), new UsesAssembler());
        $factory->register(Matcher::forType(See::class), new SeeAssembler());
        $factory->register(Matcher::forType(Since::class), new SinceAssembler());
        $factory->register(Matcher::forType(Version::class), new VersionAssembler());

        $factory->registerFallback(Matcher::forType(Tag::class), new GenericTagAssembler());

        return $factory;
    }
}
