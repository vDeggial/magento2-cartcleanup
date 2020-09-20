# Cart Cleanup - Magento 2 Module

This module removes products that are either disabled or do not exist in catalog from Carts using Cron schedule

## Requirements

*   Magento 2.x.x
*   [Hapex Core module](https://gitlab.com/deggial/magento2-core)

## Installation

*   Upload files to `Magento Home Directory`
*   Run `php bin/magento setup:upgrade` in CLI
*   Run `php bin/magento setup:di:compile` in CLI
*   Run `php bin/magento setup:static-content:deploy -f` in CLI
*   Run `php bin/magento cache:flush` in CLI
