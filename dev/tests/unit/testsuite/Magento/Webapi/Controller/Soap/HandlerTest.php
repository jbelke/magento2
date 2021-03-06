<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Webapi\Controller\Soap;

/**
 * Test for \Magento\Webapi\Controller\Soap\Handler.
 */
class HandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Webapi\Controller\Soap\Handler */
    protected $_handler;

    /** @var \Magento\ObjectManager */
    protected $_objectManagerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $_apiConfigMock;

    /** @var \Magento\Webapi\Controller\Soap\Request */
    protected $_requestMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $_authzServiceMock;

    /** @var array */
    protected $_arguments;

    protected function setUp()
    {
        /** Prepare mocks for SUT constructor. */
        $this->_apiConfigMock = $this->getMockBuilder('Magento\Webapi\Model\Soap\Config')
            ->setMethods(
                array('getServiceMethodInfo')
            )->disableOriginalConstructor()
            ->getMock();

        $this->_requestMock = $this->getMockBuilder('Magento\Webapi\Controller\Soap\Request')
            ->setMethods(array('getRequestedServices'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->_objectManagerMock = $this->getMockBuilder('Magento\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->_authzServiceMock = $this->getMockBuilder('Magento\Authz\Service\AuthorizationV1Interface')
            ->disableOriginalConstructor()
            ->getMock();

        /** Initialize SUT. */
        $this->_handler = new \Magento\Webapi\Controller\Soap\Handler(
            $this->_requestMock,
            $this->_objectManagerMock,
            $this->_apiConfigMock,
            $this->_authzServiceMock
        );

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->_handler);
        unset($this->_objectManagerMock);
        unset($this->_apiConfigMock);
        unset($this->_requestMock);
        unset($this->_authzServiceMock);
        parent::tearDown();
    }

    public function testCall()
    {
        $requestedServices = array('requestedServices');
        $this->_requestMock->expects($this->once())
            ->method('getRequestedServices')
            ->will($this->returnValue($requestedServices));
        $operationName = 'soapOperation';
        $className = 'Magento\Object';
        $methodName = 'testMethod';
        $isSecure = false;
        $aclResources = array('Magento_TestModule::resourceA');
        $this->_apiConfigMock->expects($this->once())
            ->method('getServiceMethodInfo')
            ->with($operationName, $requestedServices)
            ->will(
                $this->returnValue(
                    array(
                        \Magento\Webapi\Model\Soap\Config::KEY_CLASS => $className,
                        \Magento\Webapi\Model\Soap\Config::KEY_METHOD => $methodName,
                        \Magento\Webapi\Model\Soap\Config::KEY_IS_SECURE => $isSecure,
                        \Magento\Webapi\Model\Soap\Config::KEY_ACL_RESOURCES => $aclResources
                    )
                )
            );

        $this->_authzServiceMock->expects($this->once())->method('isAllowed')->will($this->returnValue(true));
        $serviceMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->setMethods(array($methodName))
            ->getMock();

        $expectedResult = array('foo' => 'bar');
        $serviceMock->expects($this->once())->method($methodName)->will($this->returnValue($expectedResult));
        $this->_objectManagerMock->expects($this->once())->method('get')->with($className)
            ->will($this->returnValue($serviceMock));

        /** Execute SUT. */
        $this->assertEquals(
            $expectedResult,
            $this->_handler->__call($operationName, array((object)array('field' => 1)))
        );
    }
}
