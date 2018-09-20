<?php

namespace OseilleTest\Loader;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-04-18 at 21:21:13.
 */
class ClassMapLoaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Contains original autoloaders.
     * @var array
     */
    protected $aLoaders = null;

    /**
     * Instance of the current autoloader.
     * @var \Oseille\Loader\ClassMapLoader
     */
    protected $pLoader = null;

    /**
     * Contains original include_path.
     * @var string
     */
    protected $sIncludePath = null;

    /**
     *
     */
    public function setUp()
    {
        // Store original autoloaders
        $this->aLoaders = spl_autoload_functions();
        if (! is_array($this->aLoaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->aLoaders = [ ];
        }

        // Store original include_path
        $this->sIncludePath = get_include_path();

        $this->pLoader = new \Oseille\Loader\ClassMapLoader();
    }

    /**
     *
     */
    public function tearDown()
    {
        // Restore original autoloaders
        $loaders = spl_autoload_functions();
        if (is_array($loaders)) {
            foreach ($loaders as $loader) {
                spl_autoload_unregister($loader);
            }
        }

        foreach ($this->aLoaders as $loader) {
            spl_autoload_register($loader);
        }

        // Restore original include_path
        set_include_path($this->sIncludePath);

        $this->pLoader = null;
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     * @expectedException \InvalidArgumentException
     */
    public function testRegisteringNonExistentAutoloadMapRaisesInvalidArgumentException()
    {
        $file = __DIR__ . '__foobar__';
        $this->pLoader->setOptions([ $file ]);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     * @expectedException \InvalidArgumentException
     */
    public function testValidMapFileNotReturningMapRaisesInvalidArgumentException()
    {
        $file = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'badmap.php';
        $this->assertTrue(file_exists($file));
        $this->pLoader->setOptions([ $file ]);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testAllowsRegisteringArrayAutoloadMapDirectly()
    {
        $map  = [
            'Oseille\Loader\Exception\UnderflowException' => APPLICATION_PATH . \DIRECTORY_SEPARATOR . 'src'
            . \DIRECTORY_SEPARATOR . 'Exception'
            . \DIRECTORY_SEPARATOR . 'UnderflowException.php' ];
        $this->pLoader->setOptions([ $map ]);
        $test = $this->pLoader->getAutoloadMap();
        $this->assertSame($map, $test);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testAllowsRegisteringArrayAutoloadMapViaConstructor()
    {
        $map    = [
            'Oseille\Loader\Exception\UnderflowException' => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Exception'
            . DIRECTORY_SEPARATOR . 'UnderflowException.php' ];
        $loader = new \Oseille\Loader\ClassMapLoader([ $map ]);
        $test   = $loader->getAutoloadMap();
        $this->assertSame($map, $test);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testRegisteringValidMapFilePopulatesAutoloader()
    {
        $file = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'goodmap.php';

        $this->pLoader->setOptions([ $file ]);
        $map1 = $this->pLoader->getAutoloadMap();
        $this->assertTrue(is_array($map1));
        $this->assertEquals(2, count($map1));
        // Just to make sure nothing changes after loading the same map again
        // (loadMapFromFile should just return)
        $this->pLoader->setOptions([ $file ]);
        $map2 = $this->pLoader->getAutoloadMap();
        $this->assertTrue(is_array($map2));
        $this->assertEquals(2, count($map2));
        $this->assertSame($map1, $map2);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testRegisteringMultipleMapsMergesThem()
    {
        $exceptionPath = APPLICATION_PATH . \DIRECTORY_SEPARATOR . 'src'
                . DIRECTORY_SEPARATOR . 'Exception';

        $map = [
            'Oseille\Loader\Exception\OutOfBoundsException' => $exceptionPath . \DIRECTORY_SEPARATOR . 'OutOfBoundsException.php',
            'Oseille\Loader\Exception\UnderflowException'   => $exceptionPath . \DIRECTORY_SEPARATOR . 'bogus.php' ];

        $this->pLoader->setOptions([ $map ]);

        $file = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'goodmap.php';

        $this->pLoader->setOptions([ $file ]);

        $test = $this->pLoader->getAutoloadMap();
        $this->assertTrue(is_array($test));
        $this->assertEquals(3, count($test));
        $this->assertNotEquals($map['Oseille\Loader\Exception\UnderflowException'], $test['Oseille\Loader\Exception\UnderflowException']);
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testCanRegisterMultipleMapsAtOnce()
    {
        $exceptionPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'src'
                . DIRECTORY_SEPARATOR . 'Exception';

        $map = [
            'Oseille\Loader\Exception\OutOfBoundsException' => $exceptionPath . DIRECTORY_SEPARATOR . 'OutOfBoundsException.php',
            'Oseille\Loader\Exception\UnderflowException'   => $exceptionPath . DIRECTORY_SEPARATOR . 'bogus.php' ];

        $file = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'goodmap.php';

        $this->pLoader->setOptions([ $map, $file ]);

        $test = $this->pLoader->getAutoloadMap();
        $this->assertTrue(is_array($test));
        $this->assertEquals(3, count($test));
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testRegisterMapsThrowsExceptionForNonTraversableArguments()
    {
        $tests = [ true, null, 1, 1.0, new \stdClass ];
        foreach ($tests as $test) {
            try {
                $this->pLoader->setOptions([ $test ]);
                $this->fail('Should not register non-traversable arguments');
            } catch (\InvalidArgumentException $exc) {
                $this->assertTrue(true);
            } catch (\Exception $exc) {
                $this->fail(' No the expected exception.');
            }
        }
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testAutoloadLoadsClasses()
    {
        $file   = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'ClassMappedClass.php';
        $map    = [ 'OseilleTest\Loader\provider\ClassMappedClass' => $file ];
        $this->pLoader->setOptions([ $map ]);
        $loaded = $this->pLoader->autoload('OseilleTest\Loader\provider\ClassMappedClass');
        $this->assertSame('OseilleTest\Loader\provider\ClassMappedClass', $loaded);
        $this->assertTrue(class_exists('OseilleTest\Loader\provider\ClassMappedClass', false));
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testIgnoresClassesNotInItsMap()
    {
        $file = APPLICATION_PATH_PROVIDER . \DIRECTORY_SEPARATOR . 'ClassMappedClass.php';
        $map  = [ 'OseilleTest\Loader\provider\ClassMappedClass' => $file ];
        $this->pLoader->setOptions([ $map ]);
        $this->assertFalse($this->pLoader->autoload('OseilleTest\Loader\provider\UnMappedClass'));
        $this->assertFalse(class_exists('OseilleTest\Loader\provider\UnMappedClass', false));
    }

    /**
     * @covers \Oseille\Loader\ClassMapLoader
     * @group specification
     */
    public function testRegisterRegistersCallbackWithSplAutoload()
    {
        $this->pLoader->register();
        $loaders = spl_autoload_functions();
        $this->assertTrue(count($this->aLoaders) < count($loaders));
        $test    = array_shift($loaders);
        $this->assertEquals([ $this->pLoader, 'autoload' ], $test);
    }
}
