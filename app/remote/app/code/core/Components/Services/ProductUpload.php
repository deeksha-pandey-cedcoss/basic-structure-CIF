<?php

namespace App\Core\Components\Services;

class ProductUpload extends \App\Core\Components\Services
{

    const UNIT_PRICE = 1;

    const UNIT_QTY = 10000;

    const CAPED_AMOUNT = 5;

    /**
     * @param $plan | service info from quote table
     * @param array $input | fields from quote table
     * @return int|mixed
     */
    public function getBillingPrice($plan, $input = [])
    {
        // need to make it daynamic to calculate how much product has been upoloaded in the month.
        if (!empty($input) && isset($input['unit_qty'])) {
            $uploaded_product = $input['unit_qty'];
        } else {
            $uploaded_product = 15000;
        }

        if (isset($plan['unit_price'])) {
            $unit_price = $plan['unit_price'];
        } else {
            $unit_price = self::UNIT_PRICE;
        }

        if (isset($plan['unit_qty'])) {
            $unit_qty = $plan['unit_qty'];
        } else {
            $unit_qty = self::UNIT_QTY;
        }

        if (isset($plan['caped_amount'])) {
            $caped_amount = $plan['caped_amount'];
        } else {
            $caped_amount = self::CAPED_AMOUNT;
        }

        $price = $unit_price * ceil($uploaded_product / $unit_qty);
        return min($price, $caped_amount);
    }

    public function getShortDescription($plan)
    {
        return 'Service for upload product';
    }

    public function getDescription($plan)
    {
        if (isset($plan['unit_price'])) {
            $unit_price = $plan['unit_price'];
        } else {
            $unit_price = self::UNIT_PRICE;
        }

        if (isset($plan['unit_qty'])) {
            $unit_qty = $plan['unit_qty'];
        } else {
            $unit_qty = self::UNIT_QTY;
        }

        if (isset($plan['caped_amount'])) {
            $caped_amount = $plan['caped_amount'];
        } else {
            $caped_amount = self::CAPED_AMOUNT;
        }

        return 'This service has $' . $unit_price . ' as a fixed price per month for ' . $unit_qty .
            ' product upload. Then we will charge the $' . $unit_price . ' for every increment of ' . $unit_qty .
            '. And we will charge maximum of $' . $caped_amount . ' for unlimited products.';
    }

    public function getCreatePlanHtml()
    {
        return [
            'id' => 1,
            'title' => 'Product Upload Services',
            'required' => false,
            'serviceInfo' => [
                'unit_price' => self::UNIT_PRICE,
                'unit_qty' => self::UNIT_QTY,
                'caped_amount' => self::CAPED_AMOUNT,
            ],
            'schema' => [
                [
                    'id' => 1,
                    'group' => 'Product Upload Services',
                    'formJson' => [
                        [
                            'attribute' => 'Unit Price',
                            'key' => 'unit_price',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => self::UNIT_PRICE,
                                'placeholder' => 'Unit Price',
                                'required' => true
                            ]
                        ],
                        [
                            'attribute' => 'Unit Qty',
                            'key' => 'unit_qty',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => self::UNIT_QTY,
                                'placeholder' => 'Unit Qty',
                                'required' => true
                            ]
                        ],
                        [
                            'attribute' => 'Caped Amount',
                            'key' => 'caped_amount',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => self::CAPED_AMOUNT,
                                'placeholder' => 'Caped Amount',
                                'required' => true
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function editPlanHtml($service)
    {
        return [
            'id' => $service['id'],
            'title' => $service['title'],
            'required' => $service['required'],
            'price' => $this->getInstantPrice($service['serviceInfo']),
            'serviceInfo' => [
                'unit_price' => $service['serviceInfo']['unit_price'],
                'unit_qty' => $service['serviceInfo']['unit_qty'],
                'caped_amount' => $service['serviceInfo']['caped_amount']
            ],
            'schema' => [
                [
                    'id' => $service['id'],
                    'group' => 'Product Upload Services',
                    'formJson' => [
                        [
                            'attribute' => 'Unit Price',
                            'key' => 'unit_price',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => $service['serviceInfo']['unit_price'],
                                'placeholder' => 'Unit Price',
                                'required' => true
                            ]
                        ],
                        [
                            'attribute' => 'Unit Qty',
                            'key' => 'unit_qty',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => $service['serviceInfo']['unit_qty'],
                                'placeholder' => 'Unit Qty',
                                'required' => true
                            ]
                        ],
                        [
                            'attribute' => 'Caped Amount',
                            'key' => 'caped_amount',
                            'field' => 'textfield',
                            'data' => [
                                'type' => 'text',
                                'value' => $service['serviceInfo']['caped_amount'],
                                'placeholder' => 'Caped Amount',
                                'required' => true
                            ]
                        ]
                    ]
                ]
            ],
            'priceEstimate' => [
                'title' => 'Unit Qty',
                'id' => $service['id'],
                'fields' => [
                    [
                        'label' => 'Quantity',
                        'value' => $service['serviceInfo']['unit_qty'],
                        'key' => 'unit_qty'
                    ]
                ]
            ]
        ];
    }

    public function getInstantPrice($plan)
    {
        if (isset($plan['unit_price'])) {
            return $plan['unit_price'];
        }
        return self::UNIT_PRICE;
    }

    public function preparePlanPaymentDetaills()
    {
        return '';
    }
}
