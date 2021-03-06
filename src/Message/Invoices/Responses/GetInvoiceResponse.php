<?php

namespace PHPAccounting\MyobAccountRight\Message\Invoices\Responses;

use Omnipay\Common\Message\AbstractResponse;
use PHPAccounting\MyobAccountRight\Helpers\IndexSanityCheckHelper;

/**
 * Get Invoice(s) Response
 * @package PHPAccounting\MyobAccountRight\Message\Invoices\Responses
 */
class GetInvoiceResponse extends AbstractResponse
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
        if (array_key_exists('Items', $this->data)) {
            if (count($this->data['Items']) === 0) {
                return false;
            }
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
            if ($this->data['Errors'][0]['Message'] === 'The supplied OAuth token (Bearer) is not valid') {
                return 'The access token has expired';
            }
            else {
                return $this->data['Errors'][0]['Message'];
            }
        }
        if (array_key_exists('Items', $this->data)) {
            if (count($this->data['Items']) === 0) {
                return 'NULL Returned from API or End of Pagination';
            }
        }

        return null;
    }

    /**
     * Add Contact to Invoice
     * @param $data Array of single Customer
     * @param array $invoice MYOB Invoice Object Mapping
     * @return mixed
     */
    private function parseCustomer($invoice, $data) {
        if ($data) {
            $newContact = [];
            $newContact['accounting_id'] = IndexSanityCheckHelper::indexSanityCheck('UID', $data);
            $newContact['name'] = IndexSanityCheckHelper::indexSanityCheck('Name', $data);
            $invoice['contact'] = $newContact;
        }

        return $invoice;
    }

    /**
     * Add LineItems to Invoice
     * @param $data Array of LineItems
     * @param array $invoice MYOB Invoice Object Mapping
     * @return mixed
     */
    private function parseLineItems($invoice, $data) {
        if ($data) {
            $lineItems = [];
            foreach($data as $lineItem) {
                $newLineItem = [];
                $newLineItem['accounting_id'] = IndexSanityCheckHelper::indexSanityCheck('RowID', $lineItem);
                $newLineItem['type'] = IndexSanityCheckHelper::indexSanityCheck('Type', $lineItem);
                $newLineItem['description'] = IndexSanityCheckHelper::indexSanityCheck('Description', $lineItem);
                $newLineItem['unit_amount'] = IndexSanityCheckHelper::indexSanityCheck('UnitPrice', $lineItem);
                $newLineItem['line_amount'] = IndexSanityCheckHelper::indexSanityCheck('Total', $lineItem);
                $newLineItem['quantity'] = IndexSanityCheckHelper::indexSanityCheck('ShipQuantity', $lineItem);
                $newLineItem['discount_rate'] = IndexSanityCheckHelper::indexSanityCheck('DiscountPercent', $lineItem);;
                $newLineItem['amount'] = IndexSanityCheckHelper::indexSanityCheck('Total', $lineItem);

                if (array_key_exists('TaxCode', $lineItem)) {
                    if ($lineItem['TaxCode']) {
                        $newLineItem['tax_type'] = IndexSanityCheckHelper::indexSanityCheck('Code', $lineItem['TaxCode']);
                    }
                }

                if (array_key_exists('Item', $lineItem)) {
                    if ($lineItem['Item']) {
                        $newLineItem['item_code'] = IndexSanityCheckHelper::indexSanityCheck('Number', $lineItem['Item']);
                    }
                }

                array_push($lineItems, $newLineItem);
            }

            $invoice['invoice_data'] = $lineItems;
        }

        return $invoice;
    }

    /**
     * Return all Invoices with Generic Schema Variable Assignment
     * @return array
     */
    public function getInvoices(){
        $invoices = [];
        foreach ($this->data['Items'] as $invoice) {
            $newInvoice = [];
            $newInvoice['accounting_id'] = IndexSanityCheckHelper::indexSanityCheck('UID', $invoice);
            $newInvoice['status'] = IndexSanityCheckHelper::indexSanityCheck('Status', $invoice);
            $newInvoice['sub_total'] = IndexSanityCheckHelper::indexSanityCheck('Subtotal', $invoice);
            $newInvoice['total_tax'] = IndexSanityCheckHelper::indexSanityCheck('TotalTax', $invoice);
            $newInvoice['total'] = IndexSanityCheckHelper::indexSanityCheck('TotalAmount', $invoice);
            $newInvoice['type'] = IndexSanityCheckHelper::indexSanityCheck('InvoiceType', $invoice);
            $newInvoice['invoice_number'] = IndexSanityCheckHelper::indexSanityCheck('Number', $invoice);
            $newInvoice['amount_due'] = IndexSanityCheckHelper::indexSanityCheck('BalanceDueAmount', $invoice);
            $newInvoice['date'] = IndexSanityCheckHelper::indexSanityCheck('Date', $invoice);
            $newInvoice['gst_inclusive'] = IndexSanityCheckHelper::indexSanityCheck('IsTaxInclusive', $invoice);

            if (array_key_exists('Customer', $invoice)) {
                if ($invoice['Customer']) {
                    $newInvoice = $this->parseCustomer($newInvoice, $invoice['Customer']);
                }
            }

            if (array_key_exists('Lines', $invoice)) {
                if ($invoice['Lines']) {
                    $newInvoice = $this->parseLineItems($newInvoice, $invoice['Lines']);
                }
            }

            if (array_key_exists('TotalAmount', $invoice) && array_key_exists('BalanceDueAmount', $invoice)) {
                if ($invoice['TotalAmount'] && $invoice['BalanceDueAmount']) {
                    $amountPaid = floatval($invoice['TotalAmount']) - floatval($invoice['BalanceDueAmount']);
                    if ($amountPaid) {
                        $newInvoice['amount_paid'] = $amountPaid;
                    } else {
                        $newInvoice['amount_paid'] = 0.00;
                    }

                }
            }

            if (array_key_exists('Terms', $invoice)) {
                if ($invoice['Terms']) {
                    $newInvoice['due_date'] = IndexSanityCheckHelper::indexSanityCheck('DueDate', $invoice['Terms']);
                }
            }

            array_push($invoices, $newInvoice);
        }

        return $invoices;
    }
}