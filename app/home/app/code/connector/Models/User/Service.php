<?php

namespace App\Connector\Models\User;

use App\Core\Models\BaseMongo;

class Service extends BaseMongo
{
    protected $table = 'user_service';

    protected $implicit = false;

    public function onConstruct()
    {
        $this->di = $this->getDi();
        $this->setSource($this->table);

        $this->initializeDb($this->getMultipleDbManager()->getDefaultDb());
    }

    public function initializeDatabase($db = false) {
        if ($db) {
            $this->initializeDb($db);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function canUseService()
    {
        if (property_exists($this, 'code')){
            if ($this->code == 'shopify_importer') {
                return 1;
            }
            $serviceExpireDate = new \DateTime($this->getExpiringAt());
            $now = new \DateTime();
            if ($this->charge_type == 'Prepaid' ||
                $this->charge_type == 'prepaid') {
                if ($this->getAvailableCredits() > 0) {
                    return 1;
                }
            } else {
                return 1;
            }
        }
        return 0;
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

    public function getBillTillNow()
    {
        return $this->service_charge + ($this->unpaid_credits * $this->per_unit_usage_price);
    }

    public function resetUnpaidCredits()
    {
        $this->unpaid_credits = 0;
        $this->getCollection()->findOneAndUpdate(
            ['code' => $this->code,'merchant_id'=>$this->merchant_id],
            ['$set' => ['unpaid_credits' => 0]]
        );
    }

    public function getAvailableCredits() {
        $availableCredits = 0;
        if (property_exists($this, 'available_credits')) {
            $availableCredits = $this->available_credits;
        }
        return $availableCredits;
    }

    public function getUsedCredits() {
        $totalUsedCredits = 0;
        if (property_exists($this, 'total_used_credits')) {
            $totalUsedCredits = $this->total_used_credits;
        }
        return $totalUsedCredits;
    }

    public function updateUsedCredits($creditsUsed) {
        if (property_exists($this, 'available_credits') &&
            property_exists($this, 'total_used_credits')) {
            $availableCredits = $this->available_credits - $creditsUsed;
            $totalUsedCredits = $this->total_used_credits + $creditsUsed;
            $availableCredits = ($availableCredits > 0) ? $availableCredits : 0;
            $totalUsedCredits = ($totalUsedCredits > 0) ? $totalUsedCredits : 0;
            return $this->getCollection()->findOneAndUpdate(
                ['code' => $this->code,'merchant_id'=>$this->merchant_id],
                [
                    '$set' => [
                        'available_credits' => $availableCredits,
                        'total_used_credits' => $totalUsedCredits
                    ]
                ]
            );
        }
        return false;
    }
}