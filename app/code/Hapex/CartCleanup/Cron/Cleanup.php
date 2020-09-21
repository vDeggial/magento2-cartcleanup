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
    protected $sqlSelectProducts;
    protected $sqlSelectDisabled;
    protected $sqlSelectInvalid;

    public function __construct(DataHelper $helperData, LogHelper $helperLog, ResourceConnection $resource)
    {
        parent::__construct($helperData, $helperLog);
        $this->resource = $resource;
        $this->tableCartItems = $this->resource->getTableName("quote_item");
        $this->tableProductEntity = $this->resource->getTableName("catalog_product_entity");
        $this->tableAttribute = $this->resource->getTableName("catalog_product_entity_int");
        $this->sqlSelectProducts = "select entity_id from " . $this->tableProductEntity;
        $this->sqlSelectDisabled = "select products.entity_id from " . $this->tableProductEntity . " products join " . $this->tableAttribute . " attributes on products.entity_id = attributes.entity_id where attributes.attribute_id = 97 and attributes.value <> 1";
        $this->sqlSelectInvalid = "select item_id from " . $this->tableCartItems . " where product_id not in (" . $this->sqlSelectProducts . ") or product_id in (" . $this->sqlSelectDisabled . ")";
    }

    public function cleanCarts()
    {
        switch ($this->helperData->isEnabled()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Cart Cleanup");
                    $items = $this->getInvalidCartItems();
                    $this->processCartItems($items);
                    $this->helperData->log("Ending Cart Cleanup");
                } catch (\Exception $e) {
                    $this->helperData->errorLog(__METHOD__, $e->getMessage());
                } finally {
                    return $this;
                }
        }
    }

    protected function processCartItems(&$items = [])
    {
        try {
            $this->helperData->log("- Looking for cart items which do not exist in catalog or are disabled");
            $count = count($items);

            switch ($count > 0) {
                case true:
                    $this->helperData->log("- Found $count invalid cart items");
                    $this->helperData->log("- Deleting invalid cart items");
                    $this->deleteInvalidCartItems($items);
                    break;

                default:
                    $this->helperData->log("- Found no invalid cart items");
                    break;
            }
        } catch (\Exception $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
        }
    }

    protected function getInvalidCartItems()
    {
        $items = [];
        try {
            $connection = $this->resource->getConnection();
            $result = $connection->query($this->sqlSelectInvalid);
            $items = $result->fetchAll();
        } catch (\Exception $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
            $items = [];
        } finally {
            return $items;
        }
    }

    protected function deleteInvalidCartItems(&$items = [])
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->tableCartItems;
            $itemsString = implode(",", array_column($items, "item_id"));
            $this->helperData->log($itemsString);
            $sql = "DELETE FROM $table WHERE item_id in($itemsString)";
            $result = $connection->query($sql);
            $count = $result->rowCount();
            $this->helperData->log("- Deleted $count invalid cart items");
        } catch (\Exception $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
        }
    }
}
