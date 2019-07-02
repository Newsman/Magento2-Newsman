<?php

namespace Dazoot\Newsmansmtp\Plugin\Mail\Template;

class TransportBuilderByStorePlugin
{
    /**
     * @var \Dazoot\Newsmansmtp\Model\Store
     */
    protected $storeModel;

    /**
     * Sender resolver.
     *
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @param \Dazoot\Newsmansmtp\Model\Store $storeModel
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     */
    public function __construct(
        \Dazoot\Newsmansmtp\Model\Store $storeModel,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
    ) {
        $this->storeModel = $storeModel;
        $this->senderResolver = $senderResolver;
    }

    public function beforeSetFromByStore(
        \Magento\Framework\Mail\Template\TransportBuilderByStore $subject,
        $from,
        $store
    ) {
        if (!$this->storeModel->getStoreId()) {
            $this->storeModel->setStoreId($store);
        }

        $email = $this->senderResolver->resolve($from, $store);
        $this->storeModel->setFrom($email);

        return [$from, $store];
    }
}
