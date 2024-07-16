<?php

namespace Dazoot\Newsmansmtp\Plugin\Mail\Template;

class TransportBuilderPlugin
{

    /** @var \Dazoot\Newsmansmtp\Model\Store */
    protected $storeModel;

    /**
     * @param \Dazoot\Newsmansmtp\Model\Store $storeModel
     */
    public function __construct(
        \Dazoot\Newsmansmtp\Model\Store $storeModel
    ) {
        $this->storeModel = $storeModel;
    }

    /**
     * @param \Magento\Framework\Mail\Template\TransportBuilder $subject
     * @param $templateOptions
     * @return array
     */
    public function beforeSetTemplateOptions(
        \Magento\Framework\Mail\Template\TransportBuilder $subject,
        $templateOptions
    ) {
        if (array_key_exists('store', $templateOptions)) {
            $this->storeModel->setStoreId($templateOptions['store']);
        } else {
            $this->storeModel->setStoreId(null);
        }

        return [$templateOptions];
    }
}
