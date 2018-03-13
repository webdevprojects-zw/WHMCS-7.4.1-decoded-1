<?php 
namespace WHMCS\Payment\Adapter;


interface AdapterInterface
{
    public function getConfigurationParameters();

    public function setConfigurationParameters(array $configuration);

    public function getSolutionType();

    public function setSolutionType($type);

    public function isLinkCapable();

    public function isCaptureCapable();

    public function isRefundCapable();

    public function isRemotePaymentDetailsStorageCapable();

    public function getHtmlLink(array $params);

    public function captureTransaction(array $params);

    public function refundTransaction(array $params);

    public function storePaymentDetailsRemotely(array $params);

}


