<?php
namespace PF\ProductRestriction\Model\Config\Source;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class Category implements \Magento\Framework\Option\ArrayInterface
{
    protected $categoryCollectionFactory;

    public function __construct(CollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function toOptionArray()
    {
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('name');

        $options = [];

        foreach ($categoryCollection as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName(),
            ];
        }

        return $options;
    }
}
