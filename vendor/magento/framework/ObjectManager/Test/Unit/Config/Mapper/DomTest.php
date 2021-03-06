<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ObjectManager\Test\Unit\Config\Mapper;

use \Magento\Framework\ObjectManager\Config\Mapper\Dom;

class DomTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\ObjectManager\Config\Mapper\Dom
     */
    protected $_mapper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $argumentInterpreter;

    protected function setUp()
    {
        $argumentParser = $this->getMock('\Magento\Framework\ObjectManager\Config\Mapper\ArgumentParser');
        $argumentParser->expects(
            $this->any()
        )->method(
            'parse'
        )->will(
            $this->returnCallback([$this, 'parserMockCallback'])
        );

        $booleanUtils = $this->getMock('\Magento\Framework\Stdlib\BooleanUtils');
        $booleanUtils->expects(
            $this->any()
        )->method(
            'toBoolean'
        )->will(
            $this->returnValueMap([['true', true], ['false', false]])
        );

        $this->argumentInterpreter = $this->getMock('Magento\Framework\Data\Argument\InterpreterInterface');
        $this->argumentInterpreter->expects(
            $this->any()
        )->method(
            'evaluate'
        )->with(
            ['xsi:type' => 'string', 'value' => 'test value']
        )->will(
            $this->returnValue('test value')
        );
        $this->_mapper = new Dom($this->argumentInterpreter, $booleanUtils, $argumentParser);
    }

    public function testConvert()
    {
        $dom = new \DOMDocument();
        $xmlFile = __DIR__ . '/_files/simple_di_config.xml';
        $dom->loadXML(file_get_contents($xmlFile));

        $resultFile = __DIR__ . '/_files/mapped_simple_di_config.php';
        $expectedResult = include $resultFile;
        $this->assertEquals($expectedResult, $this->_mapper->convert($dom));
    }

    /**
     * Callback for mocking parse() method of the argument parser
     *
     * @param \DOMElement $argument
     * @return string
     */
    public function parserMockCallback(\DOMElement $argument)
    {
        $this->assertNotEmpty($argument->getAttribute('name'));
        $this->assertNotEmpty($argument->getAttribute('xsi:type'));
        return ['xsi:type' => 'string', 'value' => 'test value'];
    }

    /**
     * @param string $xmlData
     * @dataProvider wrongXmlDataProvider
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid application config. Unknown node: wrong_node.
     */
    public function testMapThrowsExceptionWhenXmlHasWrongFormat($xmlData)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xmlData);
        $this->_mapper->convert($dom);
    }

    /**
     * @return array
     */
    public function wrongXmlDataProvider()
    {
        return [
            [
                '<?xml version="1.0"?><config><type name="some_type">' .
                '<wrong_node name="wrong_node" />' .
                '</type></config>',
            ],
            [
                '<?xml version="1.0"?><config><virtualType name="some_type">' .
                '<wrong_node name="wrong_node" />' .
                '</virtualType></config>'
            ],
            [
                '<?xml version="1.0"?><config>' .
                '<preference for="some_interface" type="some_class" />' .
                '<wrong_node name="wrong_node" />' .
                '</config>'
            ]
        ];
    }
}
