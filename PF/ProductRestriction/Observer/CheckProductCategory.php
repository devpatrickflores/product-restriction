<?php
namespace PF\ProductRestriction\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CheckProductCategory implements ObserverInterface
{
    protected $checkoutSession;
    protected $productFactory;
    protected $scopeConfig;
    protected $maxAllowedPrice = 80; // Maximum allowed price

    public function __construct(
        CheckoutSession $checkoutSession,
	ProductFactory $productFactory,
	ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
	$this->productFactory = $productFactory;
	$this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        // Check if the product restriction is enabled in the configuration
        if (!$this->isProductRestrictionEnabled()) {
            return; // Product restriction is not enabled, do nothing
        }

        // Get the product being added to the cart
        $product = $observer->getEvent()->getData('product');

        // Check if the product is restricted or exceeds the allowed price
        if ($this->isProductRestricted($product)) {
            // Prevent adding the product to the cart
            $this->checkoutSession->getQuote()->removeItem($product->getId());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This product cannot be added to the cart.')
            );
        }
    }

    protected function isProductRestricted($product)
    {
        // Get the product category IDs
        $categoryIds = $product->getCategoryIds();

        // Get the restricted category IDs from the configuration
        $restrictedCategoryIds = $this->scopeConfig->getValue(
            'productrestriction/general/restricted_category_ids',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Convert the string of category IDs to an array
        $restrictedCategoryIds = explode(',', $restrictedCategoryIds);

        // Check if the product belongs to any restricted category
        foreach ($categoryIds as $categoryId) {
            if (in_array($categoryId, $restrictedCategoryIds)) {
                // Product belongs to a restricted category

                // Check if the product is the only product in the cart
                $quote = $this->checkoutSession->getQuote();
                $items = $quote->getAllVisibleItems();

                if (count($items) === 1 && $items[0]->getProductId() === $product->getId()) {
                    return true; // Product is standalone, should be restricted
                } else {
                    // Product is not standalone, return false to remove restriction
                    return false;
                }
            }
        }

        return false; // Product does not belong to any restricted category
    }

    protected function isProductRestrictionEnabled()
    {
        // Get the configuration value for the 'enabled' field
        return $this->scopeConfig->isSetFlag('productrestriction/general/enabled');
    }

}
