<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Illuminate\Database\Eloquent\Model;

/**
 * CustomerBuilder.
 * 
 * Class responsible for building the customer information based on the model
 * with possible additional information, like the credit card info.
 */
class CustomerBuilder
{
    /**
     * @var Model The billable model instance (commonly User).
     */
    protected $billable;

    /**
     * @var bool Is a credit card data present on the customer information?.
     */
    protected $cardPresent = false;

    /**
     * @var array The credit card data.
     */
    protected $cardData;

    /**
     * Sets the billable into the builder scope.
     *
     * @param Model $billable The billable instance.
     */
    public function __construct(Model $billable)
    {
        $this->billable = $billable;
    }

    /**
     * Setup the credit card information into the customer builder.
     *
     * @param string      $holder   Credit card holder name.
     * @param string      $number   Credit card full number.
     * @param string      $expMonth Credit card expiration month.
     * @param string      $expYear  Credit card expiration year.
     * @param string|null $cvc      Credit card verification code.
     *
     * @return CustomerBuilder $this
     */
    public function withCreditCard($holder, $number, $expMonth, $expYear, $cvc = null)
    {
        // set the cardPresent attribute to inform a credit card data is available.
        $this->cardPresent = true;

        // set the credit card information into the builder scope.
        $this->cardData = [
            'holder_name' => $holder,
            'number' => $number,
            'expiration_month' => $expMonth,
            'expiration_year' => $expYear,
        ];

        // if there is a verification code, set it on the credit card data scope.
        if ($cvc) {
            $this->cardData['verification_code'] = $cvc;
        }

        // Return the builder instance to keep up with the fluent interface.
        return $this;
    }

    /**
     * Creates the customer into the payment gateway.
     * 
     * @return mixed
     */
    public function save()
    {
        // detects the driver
        /** @var Driver $driver */
        $driver = app('caixeiro.driver');

        // call the driver customer preparation passing the billable instance and
        // the CustomerBuilder itself.
        return $driver->prepareCustomer($this);
    }

    /**
     * @return Model The billable instance.
     */
    public function getBillable()
    {
        return $this->billable;
    }

    /**
     * @return bool Is a card present? (public access)
     */
    public function cardPresent()
    {
        return $this->cardPresent;
    }

    /**
     * @return array Returns the card data, if any.
     */
    public function getCardData()
    {
        return $this->cardData;
    }
}
