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

namespace phpDocumentor\Parser;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use phpDocumentor\Descriptor\ProjectDescriptorBuilder;
use phpDocumentor\Parser\Event\PreParsingEvent;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Php\Project;
use phpDocumentor\Reflection\ProjectFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch;

use function ini_set;
use function sys_get_temp_dir;

/**
 * Test class for \phpDocumentor\Parser\Parser.
 *
 * @coversDefaultClass \phpDocumentor\Parser\Parser
 */
final class ParserTest extends TestCase
{
    use ProphecyTrait;

    /** @var Parser */
    protected $fixture = null;

    /** @var ObjectProphecy|ProjectFactory */
    private $projectFactory;
    private EventDispatcherInterface|ObjectProphecy $eventDispatcher;

    /**
     * Instantiates a new parser object as fixture.
     */
    protected function setUp(): void
    {
        ini_set('zend.script_encoding', '');
        $this->projectFactory = $this->prophesize(ProjectFactory::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->fixture = new Parser(
            $this->projectFactory->reveal(),
            new Stopwatch(),
            new NullLogger(),
            $this->eventDispatcher->reveal(),
        );
    }

    public function testSettingAndRetrievingTheEncodingOfTheProvidedFiles(): void
    {
        $this->assertEquals('utf-8', $this->fixture->getEncoding());

        $this->fixture->setEncoding('iso-8859-1');
        $this->assertEquals('iso-8859-1', $this->fixture->getEncoding());
    }

    public function testSettingAndRetrievingTheBasePath(): void
    {
        // Arrange
        $this->assertSame('', $this->fixture->getPath());

        // Act
        $this->fixture->setPath(sys_get_temp_dir());

        // Assert
        $this->assertSame(sys_get_temp_dir(), $this->fixture->getPath());
    }

    /**
     * Tests whether the doValidation() and setValidate methods function
     * properly.
     */
    public function testValidate(): void
    {
        // defaults to false
        $this->assertEquals(false, $this->fixture->doValidation());

        $this->fixture->setValidate(true);
        $this->assertEquals(true, $this->fixture->doValidation());

        $this->fixture->setValidate(false);
        $this->assertEquals(false, $this->fixture->doValidation());
    }

    /**
     * Tests whether the getMarker() and setMarkers methods function
     * properly.
     */
    public function testMarkers(): void
    {
        $fixtureData = ['FIXME', 'TODO', 'DOIT'];

        // default is TODO and FIXME
        $this->assertEquals(['TODO', 'FIXME'], $this->fixture->getMarkers());

        $this->fixture->setMarkers($fixtureData);
        $this->assertEquals($fixtureData, $this->fixture->getMarkers());
    }

    public function testSetAndGetDefaultPackageName(): void
    {
        $parser = new Parser(
            $this->prophesize(ProjectFactory::class)->reveal(),
            $this->prophesize(Stopwatch::class)->reveal(),
            new NullLogger(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
        );

        $this->assertEquals('Default', $parser->getDefaultPackageName());

        $parser->setDefaultPackageName('test');

        $this->assertSame('test', $parser->getDefaultPackageName());
    }

    public function testFilesAreParsedByProjectFactory(): void
    {
        $file = new vfsStreamFile('my-file.php');
        vfsStream::setup()->addChild($file);

        $files = [
            new LocalFile($file->url()),
        ];

        $expectedProject = new Project(ProjectDescriptorBuilder::DEFAULT_PROJECT_NAME);
        $this->projectFactory->create(
            Argument::exact(ProjectDescriptorBuilder::DEFAULT_PROJECT_NAME),
            Argument::exact($files),
        )->shouldBeCalled()->willReturn($expectedProject);

        $project = $this->fixture->parse($files);

        $this->assertSame($expectedProject, $project);
    }

    public function testWhenParsingAnnounceWhenYouAreStarting(): void
    {
        $file = new vfsStreamFile('my-file.php');
        vfsStream::setup()->addChild($file);

        $files = [new LocalFile($file->url())];

        $preParsingEvent = null;

        $this->eventDispatcher->dispatch(
            Argument::that(static function (PreParsingEvent $event) use (&$preParsingEvent) {
                $preParsingEvent = $event;

                return true;
            }),
        )->shouldBeCalled();

        $this->projectFactory->create(Argument::any(), Argument::any())->shouldBeCalled()->willReturn(
            new Project(ProjectDescriptorBuilder::DEFAULT_PROJECT_NAME),
        );

        $this->fixture->parse($files);

        $this->assertInstanceOf(PreParsingEvent::class, $preParsingEvent);
        $this->assertSame(1, $preParsingEvent->getFileCount());
    }
}
