<?php


namespace Dazoot\Newsman\Controller\Index;
use \DateTime;

if (interface_exists("Magento\Framework\App\CsrfAwareActionInterface"))
    include __DIR__ . "/Magento23x.php";
else
    include __DIR__ . "/Magento2x.php";