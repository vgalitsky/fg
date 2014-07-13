<?php

class CL_Smiffys_Model_Order extends Mage_Sales_Model_Order {

    public function _initConfig() {
//export orders config
        $this->setXmlTemplate(Mage::getStoreConfig('smiffys_orders/export/order_xml'));
        $this->setLineXmlTemplate(Mage::getStoreConfig('smiffys_orders/export/order_line_xml'));
        $this->setExportStatus(explode(',', Mage::getStoreConfig('smiffys_orders/export/export_status')));

        $this->setSuccessStatus(Mage::getStoreConfig('smiffys_orders/export/export_success_status'));
        $this->setFailStatus(Mage::getStoreConfig('smiffys_orders/export/export_fail_status'));
//track orders config       
        $this->setTrackStatus(Mage::getStoreConfig('smiffys_orders/tracker/tracker_status'));
        $this->setTrackSuccessStatus(Mage::getStoreConfig('smiffys_orders/tracker/traker_success_status'));
        $this->setTrackCreateInvoice(Mage::getStoreConfig('smiffys_orders/tracker/traker_create_invoice'));
        $this->setTrackCreateShipment(Mage::getStoreConfig('smiffys_orders/tracker/traker_create_shipment'));


        return $this;
    }

    public function invoiceIt() {
        if ($this->canInvoice()) {

            $invoice = Mage::getModel('sales/service_order', $this)->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $transaction = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transaction->save();
        }
        else{}
            //die('cannot invoice');
    }

    /*
     * Shipment
     */
    
    /*
$trackerData = array(
                'carrier_code' => //Carrier code,
                'title' => //Carrier title,
                'number' => //Track number,
                'date' => //
            );    
*/    
    public function shipIt( $trackerData = array() ) {
        $msg = '';
        $order = $this;
        $shipment = $order->prepareShipment();
        $shipment->register();
        

        if( count( $trackerData ) ){
            $track = Mage::getModel('sales/order_shipment_track')->addData($trackerData);
            $shipment->addTrack($track);
            $msg = ' at ' . $trackerData['date'] . ' ' . $trackerData['title'].'('.$trackerData['CourierType'].')' .': '. $trackerData['number'];
        }

        $order->setIsInProcess(true);
        $order->addStatusHistoryComment('Shipped '.$msg, false);
        $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();
    }

}