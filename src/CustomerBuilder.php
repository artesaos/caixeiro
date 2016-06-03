<?php

namespace Artesaos\Caixeiro;

use Illuminate\Database\Eloquent\Model;

class CustomerBuilder
{
    protected $billable;

    protected $cardPresent = false;

    protected $cardData;

    /**
     * CustomerBuilder constructor.
     *
     * @param $billable
     */
    public function __construct($billable)
    {
        $this->billable = $billable;
    }

    /**
     * @param $holder
     * @param $number
     * @param $expMonth
     * @param $expYear
     * @param null $cvc
     *
     * @return $this
     */
    public function withCreditCard($holder, $number, $expMonth, $expYear, $cvc = null)
    {
        $this->cardPresent = true;
        $this->cardData = [
            'holder_name' => $holder,
            'number' => $number,
            'expiration_month' => $expMonth,
            'expiration_year' => $expYear,
        ];

        if ($cvc) {
            $this->cardData['verification_code'] = $cvc;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $driver = app('caixeiro.driver');

        return $driver->prepareCustomer($this->billable, $this);
    }

    /**
     * @return Model
     */
    public function getBillable()
    {
        return $this->billable;
    }

    public function cardPresent()
    {
        return $this->cardPresent;
    }

    public function getCardData()
    {
        return $this->cardData;
    }
}
