<?php

namespace Hapex\CartCleanup\Cron;

use Hapex\Core\Cron\BaseCron;
use Hapex\Core\Helper\LogHelper;
use Magento\Framework\App\ResourceConnection;
use Hapex\CartCleanup\Helper\Data as DataHelper;

class Cleanup extends BaseCron
{
    protected $resource;
    protected $connection;
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
        $this->connection = $this->resource->getConnection();
        $this->tableCartItems = $this->resource->getTableName("quote_item");
        $this->tableProductEntity = $this->resource->getTableName("catalog_product_entity");
        $this->tableAttribute = $this->resource->getTableName("catalog_product_entity_int");
        $this->sqlSelectProducts = "select entity_id from " . $this->tableProductEntity;
        $this->sqlSelectDisabled = "select products.entity_id from " . $this->tableProductEntity . " products join " . $this->tableAttribute . " attributes on products.entity_id = attributes.entity_id where attributes.attribute_id = 97 and attributes.value <> 1";
        $this->sqlSelectInvalid = "select item_id from " . $this->tableCartItems . " where product_id not in (" . $this->sqlSelectProducts . ") or product_id in (" . $this->sqlSelectDisabled . ")";
    }

    public function cleanCarts()
    {
        switch ($this->helperData->isEnabled() && $this->helperData->isEnabledCron()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Cart Cleanup Cron");
                    $items = $this->getInvalidCartItems();
                    $this->processCartItems($items);
                    $this->helperData->log("Ending Cart Cleanup Cron");
                } catch (\Throwable $e) {
                    $this->helperData->errorLog(__METHOD__, $e->getMessage());
                } finally {
                    return $this;
                }
        }
    }

    protected function processCartItems(&$items = [])
    {
        try {
            $this->helperData->log("- Looking for deleted/disabled products in carts");
            $count = count($items);

            switch ($count > 0) {
                case true:
                    $this->helperData->log("- Found $count deleted/disabled items in carts");
                    $this->helperData->log("- Deleting deleted/disabled items from carts");
                    $this->deleteInvalidCartItems(array_column($items, "item_id"));
                    break;

                default:
                    $this->helperData->log("- Found no deleted/disabled items in any cart");
                    break;
            }
        } catch (\Throwable $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
        }
    }

    protected function getInvalidCartItems()
    {
        $items = [];
        try {
            $result = $this->connection->query($this->sqlSelectInvalid);
            $items = $result->fetchAll();
        } catch (\Throwable $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
            $items = [];
        } finally {
            return $items;
        }
    }

    protected function deleteInvalidCartItems($items = [])
    {
        try {
            $table = $this->tableCartItems;
            $itemsString = implode(",", $items);
            $this->helperData->log($itemsString);
            $sql = "DELETE FROM $table WHERE item_id in($itemsString)";
            $result = $this->connection->query($sql);
            $count = $result->rowCount();
            $this->helperData->log("- Deleted $count deleted/disabled cart items");
        } catch (\Throwable $e) {
            $this->helperData->errorLog(__METHOD__, $e->getMessage());
        }
    }
}
