<?php

namespace PHPAccounting\MyobAccountRight\Message\Invoices\Responses;

use Omnipay\Common\Message\AbstractResponse;
use PHPAccounting\MyobAccountRight\Helpers\IndexSanityCheckHelper;

/**
 * Create Invoice(s) Response
 * @package PHPAccounting\MyobAccountRight\Message\Invoices\Responses
 */
class CreateInvoiceResponse extends AbstractResponse
{

    /**
     * Check Response for Error or Success
     * @return boolean
     */
    public function isSuccessful()
    {
        if(array_key_exists('Errors', $this->data)){
            return !$this->data['Errors'][0]['Severity'] == 'Error';
        }
        return true;
    }

    /**
     * Fetch Error Message from Response
     * @return string
     */
    public function getErrorMessage()
    {
        if (array_key_exists('Errors', $this->data)) {
            return $this->data['Errors'][0]['Message'];
        }
        return null;
    }



    /**
     * Return all Invoices with Generic Schema Variable Assignment
     * @return array
     */
    public function getInvoices(){
        $invoices = [];

        return $invoices;
    }
}