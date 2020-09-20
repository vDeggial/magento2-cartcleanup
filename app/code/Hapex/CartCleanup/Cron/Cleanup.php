<?php

namespace Hapex\CartCleanup\Cron;

use Hapex\Core\Cron\BaseCron;
use Hapex\Core\Helper\LogHelper;
use Magento\Framework\App\ResourceConnection;
use Hapex\CartCleanup\Helper\Data as DataHelper;

class Cleanup extends BaseCron
{
    protected $resource;
    protected $tableCartItems;
    protected $tableProductEntity;
    protected $tableAttribute;

    public function __construct(DataHelper $helperData, LogHelper $helperLog, ResourceConnection $resource)
    {
        parent::__construct($helperData, $helperLog);
        $this->resource = $resource;
        $this->tableCartItems = $this->resource->getTableName("quote_item");
        $this->tableProductEntity = $this->resource->getTableName("catalog_product_entity");
        $this->tableAttribute = $this->resource->getTableName("catalog_product_entity_int");
    }

    public function cleanCarts()
    {
        switch ($this->helperData->isEnabled()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Cart Cleanup");
                    $this->deleteInvalidCartItems();
                    $this->helperData->log("Ending Cart Cleanup");
                } catch (\Exception $e) {
                    $this->helperData->errorLog(__METHOD__, $e->getMessage());
                } finally {
                    return $this;
                }
        }
    }

    protected function deleteInvalidCartItems()
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->tableCartItems;
            $tableProduct = $this->tableProductEntity;
            $tableAttribute = $this->tableAttribute;
            $sqlProductExists = "select entity_id from $tableProduct";
            $sqlProductDisabled = "select products.entity_id from $tableProduct products join $tableAttribute attributes on products.entity_id = attributes.entity_id where attributes.attribute_id = 97 and attributes.value <> 1";
            $sqlSelect = "select item_id from $table where product_id not in ($sqlProductExists) or product_id in ($sqlProductDisabled)";
            $this->helperData->log("- Looking for cart items which do not exist in catalog or are disabled");
            $sql = "DELETE FROM $table WHERE item_id in($sqlSelect)";
            $result = $connection->query($sql);
            $count = $result->rowCount();
            $this->helperData->log("- Cleaned $count cart items");
        } catch (\Exception $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
        }
    }
}
