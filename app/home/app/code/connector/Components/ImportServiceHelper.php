<?php

namespace App\Connector\Components;

class ImportServiceHelper extends \App\Connector\Models\User\Service
{
    protected $table = 'user_service';

    protected $implicit = false;

    public $code = 'product_import';
    public $type = 'importer';//importer/uploader\
    public $started_at;
    public $expiring_at;
    public $available_credits = 0;
    public $used_credits = 0;
    public $total_used_credits = 0;

    public $service_charge ; /* fixed charge of service*/
    public $per_unit_usage_price;
    public $charge_type; // prepaid/postpaid
    public $unpaid_credits = 0; /*use for postpaid*/

    /* available credit reset date
        creadit reset after days
        service credit
    */
    public function onConstruct()
    {
        $this->di = $this->getDi();
        $this->setSource($this->table);

        $this->initializeDb($this->getMultipleDbManager()->getDefaultDb());
    }

    public function useService()
    {
        if ($this->charge_type == 'prepaid') {
            $this->getColelction()->findOneAndUpdate(
                ['code' => $this->code,'marchant_id'=>$this->merchant_id],
                ['$inc' => ['total_used_credits' => 1], '$dec' => ['available_credits' => 1]]
            );
        } else {
            $this->getColelction()->findOneAndUpdate(
                ['code' => $this->code,'marchant_id'=>$this->merchant_id],
                ['$inc' => ['total_used_credits' => 1,'unpaid_credits'=>1]]
            );
        }
        $this->total_used_credits += 1;
        $this->available_credits -= 1;
    }

    /**
     * @return bool
     */
    public function canUseService()
    {
        $serviceExpireDate = new \DateTime($this->getExpiringAt());
        $now = new \DateTime();

        if ($this->charge_type == 'prepaid') {
            if ($serviceExpireDate > $now && $this->getAvailableCredits() > 0) {
                return 1;
            }
        } else {
            return 1;
        }
        return 0;
    }

    public function getBillTillNow()
    {
        return $this->service_charge + ($this->unpaid_credits * $this->per_unit_usage_price);
    }

    public function resetUnpaidCredits()
    {
        $this->unpaid_credits = 0;
        $this->getColelction()->findOneAndUpdate(
            ['code' => $this->code,'marchant_id'=>$this->merchant_id],
            ['$set' => ['unpaid_credits' => 0]]
        );
    }
}
