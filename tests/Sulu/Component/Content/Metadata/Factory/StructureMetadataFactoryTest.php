<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Factory;

use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Loader\LoaderInterface;

class StructureMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $somethingMappingFile;

    /**
     * @var string
     */
    private $defaultMappingFile;

    /**
     * @var string
     */
    private $overriddenDefaultMappingFile;

    /**
     * @var StructureMetadata
     */
    private $somethingStructure;

    /**
     * @var StructureMetadata
     */
    private $defaultStructure;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var StructureMetadataFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = __DIR__ . '/data/cache';
        $this->somethingMappingFile = __DIR__ . '/data/page/something.xml';
        $this->defaultMappingFile = __DIR__ . '/data/other/default.xml';
        $this->overriddenDefaultMappingFile = __DIR__ . '/data/page/default.xml';

        $this->somethingStructure = $this->prophesize('Sulu\Component\Content\Metadata\StructureMetadata');
        $this->defaultStructure = $this->prophesize('Sulu\Component\Content\Metadata\StructureMetadata');
        $this->loader = $this->prophesize('Symfony\Component\Config\Loader\LoaderInterface');
        $this->factory = new StructureMetadataFactory(
            $this->loader->reveal(),
            array(
                'page' => array(
                    array(
                        'type' => 'page',
                        'path' => __DIR__ . '/data/page',
                    ),
                    array(
                        'type' => 'page',
                        'path' => __DIR__ . '/data/other',
                    ),
                ),
                'snoopet' => array(
                    array(
                        'type' => 'page',
                        'path' => __DIR__ . '/data/snoops',
                    ),
                ),
            ),
            array(
                'page' => 'something',
            ),
            $this->cacheDir
        );
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    /**
     * It should throw an exception if a non existing document alias is given.
     *
     * @expectedException \Sulu\Component\Content\Metadata\Factory\Exception\DocumentTypeNotFoundException
     * @expectedExceptionMessage Structure path for document type "non_existing" is not mapped. Mapped structure types: "page
     */
    public function testGetStructureBadType()
    {
        $this->factory->getStructureMetadata('non_existing', 'foo');
    }

    /**
     * It should throw an exception if a non existing structure type is given.
     *
     * @expectedException \Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException
     * @expectedExceptionMessage Could not load structure type "overview_not_existing" for document type "page", looked in "
     */
    public function testGetStructureNonExisting()
    {
        $this->factory->getStructureMetadata('page', 'overview_not_existing');
    }

    /**
     * It should use a default structure type if null is given.
     */
    public function testGetStructureDefault()
    {
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($this->somethingStructure->reveal());
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
    }

    /**
     * It should cache the result.
     */
    public function testCacheResult()
    {
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($this->somethingStructure->reveal());
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
    }

    /**
     * It should throw an exception if no structure type is given and no default is available.
     *
     * @expectedException \RuntimeException
     */
    public function testGetStructureDefaultNoSet()
    {
        $this->factory->getStructureMetadata('snoopet');
    }

    /**
     * Test that the structure is loaded and that the loader
     * is only called once (that the subsequent fetches do not reload
     * the metadata from the source).
     */
    public function testGetStructure()
    {
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($this->somethingStructure->reveal());
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $structure = $this->factory->getStructureMetadata('page', 'something');

        $this->assertEquals($this->somethingStructure->reveal(), $structure);

        $this->factory->getStructureMetadata('page', 'something');
        $this->factory->getStructureMetadata('page', 'something');
    }

    /**
     * Test that the structure is searched in the right direction of the configured folder.
     */
    public function testDirection()
    {
        $this->loader->load($this->defaultMappingFile, 'page')->willReturn($this->somethingStructure->reveal());

        $this->factory->getStructureMetadata('page', 'default');
    }

    /**
     * It returns all structures that are available.
     */
    public function testGetStructures()
    {
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($this->somethingStructure->reveal());
        $this->loader->load($this->defaultMappingFile, 'page')->willReturn($this->defaultStructure->reveal());
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);
        $this->loader->load($this->defaultMappingFile, 'page')->shouldBeCalledTimes(1);

        $structures = $this->factory->getStructures('page');
        $this->assertCount(3, $structures);
        $this->assertEquals($this->defaultStructure->reveal(), $structures[0]);
        $this->assertEquals($this->somethingStructure->reveal(), $structures[1]);
        $this->assertEquals($this->defaultStructure->reveal(), $structures[2]);
    }

    private function cleanUp()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);
    }
}
