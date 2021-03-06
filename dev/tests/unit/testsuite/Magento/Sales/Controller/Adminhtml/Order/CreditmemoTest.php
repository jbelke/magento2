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
namespace Magento\Sales\Controller\Adminhtml\Order;

class CreditmemoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\Creditmemo
     */
    protected $_controller;

    /**
     * @var \Magento\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_responseMock;

    /**
     * @var \Magento\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_requestMock;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_sessionMock;

    /**
     * @var \Magento\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_objectManager;

    /**
     * @var \Magento\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_messageManager;

    /**
     * Init model for future tests
     */
    protected function setUp()
    {
        $helper = new \Magento\TestFramework\Helper\ObjectManager($this);
        $this->_responseMock = $this->getMock('Magento\App\Response\Http', array(), array(), '', false);
        $this->_responseMock->headersSentThrowsException = false;
        $this->_requestMock = $this->getMock('Magento\App\Request\Http', array(), array(), '', false);
        $objectManager = new \Magento\TestFramework\Helper\ObjectManager($this);
        $constructArguments = $objectManager->getConstructArguments('Magento\Backend\Model\Session',
            array('storage' => new \Magento\Session\Storage));
        $this->_sessionMock = $this->getMock('Magento\Backend\Model\Session',
            array('setFormData'), $constructArguments
        );
        $this->_objectManager = $this->getMock('Magento\ObjectManager', array(), array(), '', false);
        $registryMock = $this->getMock('Magento\Core\Model\Registry', array(), array(), '', false, false);
        $this->_objectManager->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Magento\Core\Model\Registry'))
            ->will($this->returnValue($registryMock));
        $this->_messageManager = $this->getMock('\Magento\Message\ManagerInterface', array(), array(), '', false);

        $arguments = array(
            'response' => $this->_responseMock,
            'request' => $this->_requestMock,
            'session' => $this->_sessionMock,
            'objectManager' => $this->_objectManager,
            'messageManager' => $this->_messageManager
        );

        $context = $helper->getObject('Magento\Backend\App\Action\Context', $arguments);

        $this->_controller = $helper->getObject('Magento\Sales\Controller\Adminhtml\Order\Creditmemo',
            array('context' => $context));
    }

    /**
     * Test saveAction when was chosen online refund with refund to store credit
     */
    public function testSaveActionOnlineRefundToStoreCredit()
    {
        $data = array(
            'comment_text' => '',
            'do_offline' => '0',
            'refund_customerbalance_return_enable' => '1'
        );
        $creditmemoId = '1';
        $this->_requestMock->expects($this->once())
            ->method('getPost')->with('creditmemo')->will($this->returnValue($data));
        $this->_requestMock->expects($this->at(1))
            ->method('getParam')->with('creditmemo_id')->will($this->returnValue($creditmemoId));
        $this->_requestMock->expects($this->any())
            ->method('getParam')->will($this->returnValue(null));

        $creditmemoMock = $this->getMock(
            'Magento\Sales\Model\Order\Creditmemo',
            array('load', 'getGrandTotal', '__wakeup'),
            array(),
            '',
            false
        );
        $creditmemoMock->expects($this->once())->method('load')
            ->with($this->equalTo($creditmemoId))->will($this->returnSelf());
        $creditmemoMock->expects($this->once())->method('getGrandTotal')->will($this->returnValue('1'));
        $this->_objectManager->expects($this->once())
            ->method('create')
            ->with($this->equalTo('Magento\Sales\Model\Order\Creditmemo'))
            ->will($this->returnValue($creditmemoMock));

        $this->_setSaveActionExpectationForMageCoreException($data,
            'Cannot create online refund for Refund to Store Credit.'
        );

        $this->_controller->saveAction();
    }

    /**
     * Test saveAction when was credit memo total is not positive
     */
    public function testSaveActionWithNegativeCreditmemo()
    {
        $data = array('comment_text' => '');
        $creditmemoId = '1';
        $this->_requestMock->expects($this->once())
            ->method('getPost')->with('creditmemo')->will($this->returnValue($data));
        $this->_requestMock->expects($this->at(1))
            ->method('getParam')->with('creditmemo_id')->will($this->returnValue($creditmemoId));
        $this->_requestMock->expects($this->any())
            ->method('getParam')->will($this->returnValue(null));

        $creditmemoMock = $this->getMock(
            'Magento\Sales\Model\Order\Creditmemo',
            array('load', 'getGrandTotal', 'getAllowZeroGrandTotal', '__wakeup'),
            array(),
            '',
            false
        );
        $creditmemoMock->expects($this->once())->method('load')
            ->with($this->equalTo($creditmemoId))->will($this->returnSelf());
        $creditmemoMock->expects($this->once())->method('getGrandTotal')->will($this->returnValue('0'));
        $creditmemoMock->expects($this->once())->method('getAllowZeroGrandTotal')->will($this->returnValue(false));
        $this->_objectManager->expects($this->once())
            ->method('create')
            ->with($this->equalTo('Magento\Sales\Model\Order\Creditmemo'))
            ->will($this->returnValue($creditmemoMock));

        $this->_setSaveActionExpectationForMageCoreException($data, 'Credit memo\'s total must be positive.');

        $this->_controller->saveAction();
    }

    /**
     * Set expectations in case of \Magento\Core\Exception for saveAction method
     *
     * @param array $data
     * @param string $errorMessage
     */
    protected function _setSaveActionExpectationForMageCoreException($data, $errorMessage)
    {
        $this->_messageManager->expects($this->once())
            ->method('addError')
            ->with($this->equalTo($errorMessage));
        $this->_sessionMock->expects($this->once())
            ->method('setFormData')
            ->with($this->equalTo($data));

        $this->_responseMock->expects($this->once())
            ->method('setRedirect');
    }
}
