<?php
class OfdReceiptRequest
{
    const SCRIPT_NAME = 'receipt.php';
    public     $merchantId;
    public     $operationType = 'payment';
    protected  $items         = [];
    protected  $params        = [];
    public     $paymentId;
    /**
     * Создание объекта
     * @param int $merchantId ID магазина
     * @param int $paymentId  ID транзакции 
     * @param array $conf настройки платрона
     */
    public function __construct($merchantId, $paymentId)
    {
        $this->merchantId = $merchantId;
        $this->paymentId  = $paymentId;
    }
    public function setItems($items)
    {
        $this->items = $items;
    }
    public function getAction()
    {
        return self::SCRIPT_NAME;
    }
    public function createParamSign($secretKey)
    {
        $params = $this->getAttributeAsArray();
        $params['pg_salt'] = 'salt';
        $params['pg_sig']  = PG_Signature::make(self::SCRIPT_NAME, $params, $secretKey);
        $this->params = $params;
    }
    public function getAttributeAsArray()
    {
        $result = array();
        $result['pg_merchant_id']    = $this->merchantId;
        $result['pg_operation_type'] = $this->operationType;
        $result['pg_payment_id']     = $this->paymentId;
        foreach ($this->items as $item) {
            $result['pg_items'][] = $item->getAttributeAsArray();
        }
        return $result;
    }
    public function getParams()
    {
        return $this->params;
    }
    public function makeXml()
    {
        //var_dump($this->params);
        $xmlElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
        foreach ($this->params as $paramName => $paramValue) {
            if ($paramName == 'pg_items') {
                //$itemsElement = $xmlElement->addChild($paramName);
                foreach ($paramValue as $itemParams) {
                    $itemElement = $xmlElement->addChild($paramName);
                    foreach ($itemParams as $itemParamName => $itemParamValue) {
                        $itemElement->addChild($itemParamName, $itemParamValue);
                    }
                }
                continue;
            }
            $xmlElement->addChild($paramName, $paramValue);
        }
        return $xmlElement->asXML();
    }
}