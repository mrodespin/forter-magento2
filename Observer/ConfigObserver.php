<?php

namespace Forter\Forter\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Forter\Forter\Model\AbstractApi;
use Forter\Forter\Model\Config;

class ConfigObserver implements \Magento\Framework\Event\ObserverInterface
{
  const SETTINGS_API_ENDPOINT = 'https://api.forter-secure.com/ext/settings/';

  public function __construct(
      AbstractApi $abstractApi,
      Config $forterConfig
  )
  {
      $this->abstractApi = $abstractApi;
      $this->forterConfig = $forterConfig;
  }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer) {
      $json = [
        "general" => [
          "active" => $this->forterConfig->isEnabled(),
          "site_id" => $this->forterConfig->getSiteId(),
          "secret_key" => $this->forterConfig->getSecretKey(),
          "module_version" => $this->forterConfig->getModuleVersion(),
          "api_version" => $this->forterConfig->getApiVersion(),
          "debug_mode" => $this->forterConfig->isDebugEnabled(),
          "sandbox_mode" => $this->forterConfig->isSandboxMode()
        ],
        "pre_post_desicion" => [
          "pre_post_Select" => $this->forterConfig->getPrePostDesicionMsg('pre_post_Select'),
          "pre_decline" => $this->forterConfig->getPrePostDesicionMsg('decline_pre'),
          "pre_thanks_msg" => $this->forterConfig->getPrePostDesicionMsg('pre_thanks_msg'),
          "post_decline" => $this->forterConfig->getPrePostDesicionMsg('decline_post'),
          "post_approve" => $this->forterConfig->getPrePostDesicionMsg('capture_invoice'),
          "post_thanks_msg" => $this->forterConfig->getPrePostDesicionMsg('post_thanks_msg')
        ],
        "store" => [
          "storeId" => $this->forterConfig->getStoreId()
        ],
        "connection_information" => $this->forterConfig->getTimeOutSettings(),
        "eventTime" => time()
      ];

      try{
        $url = self::SETTINGS_API_ENDPOINT;
        $response = $this->abstractApi->sendApiRequest($url,json_encode($json));
      } catch (\Exception $e) {
        $this->abstractApi->reportToForterOnCatch($e);
        throw new \Exception ($e->getMessage());
      }
    }
}
