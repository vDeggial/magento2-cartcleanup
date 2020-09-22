<?php

namespace Hapex\CartCleanup\Observer;

use Hapex\Core\Helper\LogHelper;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Hapex\Core\Observer\BaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Hapex\CartCleanup\Helper\Data as DataHelper;

class LoginObserver extends BaseObserver
{

    protected $cart;
    protected $_productRepository;
    protected $session;
    protected $productModel;

    public function __construct(
        DataHelper $helperData,
        LogHelper $helperLog,
        ManagerInterface $messageManager,
        Cart $cart,
        Session $checkoutSession,
        Product $productModel
    ) {
        parent::__construct($helperData, $helperLog, $messageManager);
        $this->cart = $cart;
        $this->session = $checkoutSession;
        $this->productModel = $productModel;
    }

    public function execute(Observer $observer)
    {
        $success = false;
        try {
            $this->helperData->log("");
            $this->helperData->log("Starting Cart Cleanup Login Observer");
            $customerId = $observer->getEvent()->getCustomer()->getId();
            $this->helperData->log("- Customer with ID $customerId has logged in");
            $this->helperData->log("- Checking customer's Cart for invalid products");
            $cartItems = $this->getCartItems();
            $count = count($cartItems);
            switch ($count > 0) {
                case true:
                    $this->helperData->log("- Found $count Items in the Cart");
                    $this->helperData->log("- Checking Cart Items");
                    $success = $this->processCartItems($cartItems);
                    break;

                default:
                    $this->helperData->log("- Empty Cart detected");
                    $success = true;
                    break;
            }
            $this->helperData->log("Ending Cart Cleanup Login Observer");
        } catch (\Exception $e) {
            $this->helperLog->errorLog(__METHOD__, $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $success = false;
        } finally {
            return $success;
        }
    }

    protected function getCartItems()
    {
        return $this->session->getQuote()->getAllItems();
    }

    protected function processCartItems($cartItems = [])
    {
        try {
            $count = 0;
            foreach ($cartItems as $item) {
                $productId = $item->getProductId();
                $product = $this->productModel->load($productId);
                switch (!$product || !$product->getStatus()) {
                    case true:
                        $this->helperData->log("-- Found product $productId in the Cart that either no longer exists or is Disabled");
                        $this->cart->removeItem($item->getItemId())->save();
                        $this->helperData->log("-- Removed invalid product $productId from the Cart");
                        $count++;
                        break;
                }
            }
            $this->helperData->log("- Removed $count invalid products from the Cart");
            return true;
        } catch (\Exception $e) {
            $this->helperLog->errorLog(__METHOD__, $e->getMessage());
            return false;
        }
    }
}
