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

namespace phpDocumentor\Descriptor;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use phpDocumentor\Descriptor\Interfaces\ChildInterface;
use phpDocumentor\Descriptor\Tag\AuthorDescriptor;
use phpDocumentor\Descriptor\Tag\ReturnDescriptor;
use phpDocumentor\Descriptor\Tag\VersionDescriptor;
use phpDocumentor\Descriptor\ValueObjects\Visibility;
use phpDocumentor\Descriptor\ValueObjects\VisibilityModifier;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types\String_;

use function iterator_to_array;

/**
 * Tests the functionality for the MethodDescriptor class.
 *
 * @coversDefaultClass \phpDocumentor\Descriptor\MethodDescriptor
 */
final class MethodDescriptorTest extends MockeryTestCase
{
    use AttributedTestTrait;

    private MethodDescriptor $fixture;

    /**
     * Creates a new (empty) fixture object.
     */
    protected function setUp(): void
    {
        $this->fixture = new MethodDescriptor();
        $this->fixture->setName('method');
    }

    private function getParent(): ChildInterface|null
    {
        return null;
    }

    public function testSettingAndGettingAParent(): void
    {
        $parent = new ClassDescriptor();
        $parent->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));

        $this->assertNull($this->fixture->getParent());

        $this->fixture->setParent($parent);

        $this->assertSame($parent, $this->fixture->getParent());
    }

    public function testSettingAndGettingArguments(): void
    {
        $this->assertInstanceOf(Collection::class, $this->fixture->getArguments());
        $this->assertCount(0, iterator_to_array($this->fixture->getArguments()));

        $argument = new ArgumentDescriptor();
        $argument->setName('name');
        $collection = new Collection([$argument]);

        $this->fixture->setArguments($collection);

        $this->assertInstanceOf(Collection::class, $this->fixture->getArguments());
        $argumentsAsArray = iterator_to_array($this->fixture->getArguments());
        $this->assertCount(1, $argumentsAsArray);
        $this->assertSame($argument, $argumentsAsArray['name']);
        $this->assertSame($argument->getMethod(), $this->fixture);
    }

    public function testAddingAnArgument(): void
    {
        $this->assertInstanceOf(Collection::class, $this->fixture->getArguments());
        $this->assertCount(0, iterator_to_array($this->fixture->getArguments()));

        $argument = new ArgumentDescriptor();
        $argument->setName('name');

        $this->fixture->addArgument('name', $argument);

        $this->assertInstanceOf(Collection::class, $this->fixture->getArguments());
        $argumentsAsArray = iterator_to_array($this->fixture->getArguments());
        $this->assertCount(1, $argumentsAsArray);
        $this->assertSame($argument, $argumentsAsArray['name']);
        $this->assertSame($argument->getMethod(), $this->fixture);
    }

    public function testSettingAndGettingWhetherMethodIsAbstract(): void
    {
        $this->assertFalse($this->fixture->isAbstract());

        $this->fixture->setAbstract(true);

        $this->assertTrue($this->fixture->isAbstract());
    }

    public function testSettingAndGettingWhetherMethodIsFinal(): void
    {
        $this->assertFalse($this->fixture->isFinal());

        $this->fixture->setFinal(true);

        $this->assertTrue($this->fixture->isFinal());
    }

    public function testSettingAndGettingWhetherMethodIsStatic(): void
    {
        $this->assertFalse($this->fixture->isStatic());

        $this->fixture->setStatic(true);

        $this->assertTrue($this->fixture->isStatic());
    }

    public function testSettingAndGettingVisibility(): void
    {
        $this->assertEquals('public', $this->fixture->getVisibility());

        $this->fixture->setVisibility(new Visibility(VisibilityModifier::PRIVATE));

        $this->assertEquals('private', $this->fixture->getVisibility());
    }

    public function testRetrieveReturnTagForResponse(): void
    {
        $returnDescriptor = new ReturnDescriptor('return');
        $returnDescriptor->setType(new String_());

        $this->assertNull($this->fixture->getResponse()->getType());

        $this->fixture->getTags()->set('return', new Collection([$returnDescriptor]));

        $this->assertSame($returnDescriptor, $this->fixture->getResponse());
    }

    public function testGetResponseReturnsReturnType(): void
    {
        $returnType = new String_();
        $this->fixture->setReturnType($returnType);

        $this->assertSame($returnType, $this->fixture->getResponse()->getType());
    }

    public function testRetrieveFileAssociatedWithAMethod(): void
    {
        // Arrange
        $file = $this->whenFixtureIsRelatedToAClassWithFile();

        // Act
        $result = $this->fixture->getFile();

        // Assert
        $this->assertSame($file, $result);
    }

    public function testReturnTagsInheritWhenNoneArePresent(): void
    {
        $this->assertInstanceOf(Collection::class, $this->fixture->getReturn());
        $this->assertSame(0, $this->fixture->getReturn()->count());

        $returnTagDescriptor = new ReturnDescriptor('return');
        $returnCollection = new Collection([$returnTagDescriptor]);
        $this->fixture->getTags()->clear();
        $parentProperty = $this->whenFixtureHasMethodInParentClassWithSameName($this->fixture->getName());
        $parentProperty->getTags()->set('return', $returnCollection);

        $result = $this->fixture->getReturn();

        $this->assertEquals($returnCollection, $result);
    }

    public function testParamTagsInheritWhenNoneArePresent(): void
    {
        $this->assertInstanceOf(Collection::class, $this->fixture->getParam());
        $this->assertSame(0, $this->fixture->getParam()->count());

        $paramTagDescriptor = new AuthorDescriptor('param');
        $paramCollection = new Collection([$paramTagDescriptor]);
        $this->fixture->getTags()->clear();
        $parentProperty = $this->whenFixtureHasMethodInParentClassWithSameName($this->fixture->getName());
        $parentProperty->getTags()->set('param', $paramCollection);

        $result = $this->fixture->getParam();

        $this->assertSame($paramCollection, $result);
    }

    public function testElementDoesNotInheritWhenNoParents(): void
    {
        $this->assertNull($this->fixture->getInheritedElement());

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);

        $this->assertNull($this->fixture->getInheritedElement());
    }

    public function testElementInheritanceCaches(): void
    {
        $parentClass = new ClassDescriptor();
        $parentClass->setAbstract(true);
        $parentClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\AbstractClass'));
        $parentMethod = new MethodDescriptor();
        $parentMethod->setName('myMethod');
        $parentMethod->setParent($parentClass);
        $parentClass->getMethods()->set($parentMethod->getName(), $parentMethod);

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setParent($parentClass);
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->fixture->getInheritedElement();
        $this->assertSame($parentMethod, $this->fixture->getInheritedElement());
    }

    public function testElementInheritsWhenExtending(): void
    {
        $parentClass = new ClassDescriptor();
        $parentClass->setAbstract(true);
        $parentClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\AbstractClass'));
        $parentMethod = new MethodDescriptor();
        $parentMethod->setName('myMethod');
        $parentMethod->setParent($parentClass);
        $parentClass->getMethods()->set($parentMethod->getName(), $parentMethod);

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setParent($parentClass);
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->assertSame($parentMethod, $this->fixture->getInheritedElement());
    }

    public function testElementInheritsRecursivelyWhenExtending(): void
    {
        $parentClass1 = new ClassDescriptor();
        $parentClass1->setAbstract(true);
        $parentClass1->setFullyQualifiedStructuralElementName(new Fqsen('\My\AbstractClass1'));
        $parentMethod1 = new MethodDescriptor();
        $parentMethod1->setName('myMethod');
        $parentMethod1->setParent($parentClass1);
        $parentClass1->getMethods()->set($parentMethod1->getName(), $parentMethod1);

        $parentClass2 = new ClassDescriptor();
        $parentClass2->setAbstract(true);
        $parentClass2->setFullyQualifiedStructuralElementName(new Fqsen('\My\AbstractClass2'));
        $parentClass2->setParent($parentClass1);

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setParent($parentClass2);
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->assertSame($parentMethod1, $this->fixture->getInheritedElement());
    }

    public function testElementInheritsWhenImplementing(): void
    {
        $interface1 = new InterfaceDescriptor();
        $interface1->setFullyQualifiedStructuralElementName(new Fqsen('\My\Interface1'));

        $interface2 = new InterfaceDescriptor();
        $interface2->setFullyQualifiedStructuralElementName(new Fqsen('\My\Interface2'));
        $interface2->setParent(new Collection([$interface1]));
        $interfaceMethod2 = new MethodDescriptor();
        $interfaceMethod2->setName('myMethod');
        $interface2->setMethods(new Collection([$interfaceMethod2]));
        $interface2->getMethods()->set($interfaceMethod2->getName(), $interfaceMethod2);

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setInterfaces(new Collection([$interface1, $interface2]));
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->assertSame($interfaceMethod2, $this->fixture->getInheritedElement());
    }

    public function testElementInheritsRecursivelyWhenImplementing(): void
    {
        $interface1 = new InterfaceDescriptor();
        $interface1->setFullyQualifiedStructuralElementName(new Fqsen('\My\Interface1'));
        $interfaceMethod1 = new MethodDescriptor();
        $interfaceMethod1->setName('myMethod');
        $interfaceMethod1->setParent($interface1);
        $interface1->setMethods(new Collection([$interfaceMethod1]));
        $interface1->getMethods()->set($interfaceMethod1->getName(), $interfaceMethod1);

        $interface2 = new InterfaceDescriptor();
        $interface2->setFullyQualifiedStructuralElementName(new Fqsen('\My\Interface2'));
        $interface2->setParent(new Collection([$interface1]));

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setInterfaces(new Collection([$interface2]));
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->assertSame($interfaceMethod1, $this->fixture->getInheritedElement());
    }

    public function testElementInheritsRecursivelyWhenExtendingAndImplementing(): void
    {
        $interface = new InterfaceDescriptor();
        $interface->setFullyQualifiedStructuralElementName(new Fqsen('\My\Interface'));
        $interfaceMethod = new MethodDescriptor();
        $interfaceMethod->setName('myMethod');
        $interfaceMethod->setParent($interface);
        $interface->setMethods(new Collection([$interfaceMethod]));
        $interface->getMethods()->set($interfaceMethod->getName(), $interfaceMethod);

        $parentClass = new ClassDescriptor();
        $parentClass->setAbstract(true);
        $parentClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\AbstractClass'));
        $parentClass->setInterfaces(new Collection([$interface]));

        $associatedClass = new ClassDescriptor();
        $associatedClass->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $associatedClass->setParent($parentClass);
        $this->fixture->setName('myMethod');
        $this->fixture->setParent($associatedClass);
        $associatedClass->getMethods()->set($this->fixture->getName(), $this->fixture);

        $this->assertSame($interfaceMethod, $this->fixture->getInheritedElement());
    }

    /** @covers \phpDocumentor\Descriptor\DescriptorAbstract::getAuthor */
    public function testAuthorTagsInheritWhenNoneArePresent(): void
    {
        // Arrange
        $authorTagDescriptor = new AuthorDescriptor('author');
        $authorCollection = new Collection([$authorTagDescriptor]);
        $this->fixture->getTags()->clear();
        $parentProperty = $this->whenFixtureHasMethodInParentClassWithSameName($this->fixture->getName());
        $parentProperty->getTags()->set('author', $authorCollection);

        // Act
        $result = $this->fixture->getAuthor();

        // Assert
        $this->assertSame($authorCollection, $result);
    }

    /** @covers \phpDocumentor\Descriptor\DescriptorAbstract::getVersion */
    public function testVersionTagsInheritWhenNoneArePresent(): void
    {
        // Arrange
        $versionTagDescriptor = new VersionDescriptor('version');
        $versionCollection = new Collection([$versionTagDescriptor]);
        $this->fixture->getTags()->clear();
        $parentProperty = $this->whenFixtureHasMethodInParentClassWithSameName($this->fixture->getName());
        $parentProperty->getTags()->set('version', $versionCollection);

        // Act
        $result = $this->fixture->getVersion();

        // Assert
        $this->assertSame($versionCollection, $result);
    }

    /** @covers \phpDocumentor\Descriptor\DescriptorAbstract::getCopyright */
    public function testCopyrightTagsInheritWhenNoneArePresent(): void
    {
        // Arrange
        $copyrightTagDescriptor = new TagDescriptor('copyright');
        $copyrightCollection = new Collection([$copyrightTagDescriptor]);
        $this->fixture->getTags()->clear();
        $parentProperty = $this->whenFixtureHasMethodInParentClassWithSameName($this->fixture->getName());
        $parentProperty->getTags()->set('copyright', $copyrightCollection);

        // Act
        $result = $this->fixture->getCopyright();

        // Assert
        $this->assertSame($copyrightCollection, $result);
    }

    /**
     * Sets up mocks as such that the fixture has a parent class, with a file.
     *
     * @return m\MockInterface|FileDescriptor
     */
    private function whenFixtureIsRelatedToAClassWithFile()
    {
        $file = m::mock(FileDescriptor::class);
        $parent = m::mock(ClassDescriptor::class);
        $parent->shouldReceive('getFile')->andReturn($file);
        $parent->shouldReceive('getFullyQualifiedStructuralElementName')->andReturn(new Fqsen('\My\Class1'));
        $this->fixture->setParent($parent);

        return $file;
    }

    /** @param string $name The name of the current method. */
    private function whenFixtureHasMethodInParentClassWithSameName(string $name): MethodDescriptor
    {
        $result = new MethodDescriptor();
        $result->setName($name);

        $parent = new ClassDescriptor();
        $parent->setFullyQualifiedStructuralElementName(new Fqsen('\My\Super\Class'));
        $parent->getMethods()->set($name, $result);

        $class = new ClassDescriptor();
        $class->setFullyQualifiedStructuralElementName(new Fqsen('\My\Class'));
        $class->setParent($parent);

        $this->fixture->setParent($class);

        return $result;
    }
}
