<?php

namespace Hapex\CartCleanup\Helper;

use Hapex\Core\Helper\DataHelper;

class Data extends DataHelper
{
    protected const XML_PATH_CONFIG_ENABLED = "hapex_cartcleanup/general/enable";
    protected const XML_PATH_CONFIG_CRON_ENABLED = "hapex_cartcleanup/general/enable_cron";
    protected const XML_PATH_CONFIG_LOGIN_ENABLED = "hapex_cartcleanup/general/enable_login";
    protected const FILE_PATH_LOG = "hapex_cart_cleanup";

    public function isEnabled()
    {
        return $this->getConfigFlag(self::XML_PATH_CONFIG_ENABLED);
    }

    public function isEnabledLogin()
    {
        return $this->getConfigFlag(self::XML_PATH_CONFIG_LOGIN_ENABLED);
    }

    public function isEnabledCron()
    {
        return $this->getConfigFlag(self::XML_PATH_CONFIG_CRON_ENABLED);
    }

    public function log($message)
    {
        $this->helperLog->printLog(self::FILE_PATH_LOG, $message);
    }
}
