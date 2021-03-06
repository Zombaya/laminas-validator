<?php

/**
 * @see       https://github.com/laminas/laminas-validator for the canonical source repository
 * @copyright https://github.com/laminas/laminas-validator/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-validator/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Validator;

use Laminas\Uri\Exception\InvalidArgumentException;
use Laminas\Uri\Http;
use Laminas\Uri\Uri;
use Laminas\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Validator
 */
class UriTest extends TestCase
{
    /**
     * @var \Laminas\Validator\Uri
     */
    protected $validator;

    /**
     * Creates a new Uri Validator object for each test method
     *
     * @return void
     */
    public function setUp()
    {
        $this->validator = new Validator\Uri();
    }

    public function testHasDefaultSettingsAndLazyLoadsUriHandler()
    {
        $validator = $this->validator;
        $uriHandler = $validator->getUriHandler();
        $this->assertInstanceOf(Uri::class, $uriHandler);
        $this->assertTrue($validator->getAllowRelative());
        $this->assertTrue($validator->getAllowAbsolute());
    }

    public function testConstructorWithArraySetsOptions()
    {
        $uriMock = $this->createMock(Uri::class);
        $validator = new Validator\Uri([
            'uriHandler' => $uriMock,
            'allowRelative' => false,
            'allowAbsolute' => false,
        ]);
        $this->assertEquals($uriMock, $validator->getUriHandler());
        $this->assertFalse($validator->getAllowRelative());
        $this->assertFalse($validator->getAllowAbsolute());
    }

    public function testConstructorWithArgsSetsOptions()
    {
        $uriMock = $this->createMock(Uri::class);
        $validator = new Validator\Uri($uriMock, false, false);
        $this->assertEquals($uriMock, $validator->getUriHandler());
        $this->assertFalse($validator->getAllowRelative());
        $this->assertFalse($validator->getAllowAbsolute());
    }

    public function allowOptionsDataProvider()
    {
        return [
            //    allowAbsolute allowRelative isAbsolute isRelative isValid expects
            [true,         true,         true,      false,     true,   true],
            [true,         true,         false,     true,      true,   true],
            [false,        true,         true,      false,     true,   false],
            [false,        true,         false,     true,      true,   true],
            [true,         false,        true,      false,     true,   true],
            [true,         false,        false,     true,      true,   false],
            [false,        false,        true,      false,     true,   false],
            [false,        false,        false,     true,      true,   false],
            [true,         true,         false,     false,     false,  false],
        ];
    }

    /**
     * @dataProvider allowOptionsDataProvider
     */
    public function testUriHandlerBehaviorWithAllowSettings(
        $allowAbsolute,
        $allowRelative,
        $isAbsolute,
        $isRelative,
        $isValid,
        $expects
    ) {
        $uriMock = $this->getMockBuilder(Uri::class)
            ->setConstructorArgs(['parse', 'isValid', 'isAbsolute', 'isValidRelative'])
            ->getMock();
        $uriMock->expects($this->once())
            ->method('isValid')->will($this->returnValue($isValid));
        $uriMock->expects($this->any())
            ->method('isAbsolute')->will($this->returnValue($isAbsolute));
        $uriMock->expects($this->any())
            ->method('isValidRelative')->will($this->returnValue($isRelative));

        $this->validator->setUriHandler($uriMock)
            ->setAllowAbsolute($allowAbsolute)
            ->setAllowRelative($allowRelative);

        $this->assertEquals($expects, $this->validator->isValid('uri'));
    }

    public function testUriHandlerThrowsExceptionInParseMethodNotValid()
    {
        $uriMock = $this->createMock(Uri::class);
        $uriMock->expects($this->once())
            ->method('parse')
            ->will($this->throwException(new InvalidArgumentException()));

        $this->validator->setUriHandler($uriMock);
        $this->assertFalse($this->validator->isValid('uri'));
    }

    /**
     * Ensures that getMessages() returns expected default value
     *
     * @return void
     */
    public function testGetMessages()
    {
        $this->assertEquals([], $this->validator->getMessages());
    }

    public function testEqualsMessageTemplates()
    {
        $validator = $this->validator;
        $this->assertObjectHasAttribute('messageTemplates', $validator);
        $this->assertAttributeEquals($validator->getOption('messageTemplates'), 'messageTemplates', $validator);
    }

    public function testUriHandlerCanBeSpecifiedAsString()
    {
        $this->validator->setUriHandler(Http::class);
        $this->assertInstanceOf(Http::class, $this->validator->getUriHandler());
    }

    public function testUriHandlerStringInvalidClassThrowsException()
    {
        $this->expectException(Validator\Exception\InvalidArgumentException::class);
        $this->validator->setUriHandler(\stdClass::class);
    }

    public function testUriHandlerInvalidTypeThrowsException()
    {
        $this->expectException(Validator\Exception\InvalidArgumentException::class);
        $this->validator->setUriHandler(new \stdClass());
    }

    public function invalidValueTypes()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['http://example.com']],
            'object'     => [(object) ['uri' => 'http://example.com']],
        ];
    }

    /**
     * @dataProvider invalidValueTypes
     */
    public function testIsValidReturnsFalseWhenProvidedUnsupportedType($value)
    {
        $this->assertFalse($this->validator->isValid($value));
    }
}
