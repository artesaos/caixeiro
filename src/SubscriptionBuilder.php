<?php

namespace Artesaos\Caixeiro;

class SubscriptionBuilder
{
    /**
     * The user model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $billable;

    /**
     * The name of the plan being subscribed to.
     *
     * @var string
     */
    protected $planName;

    /**
     * Payment method other than credit card, only if supported by the plan.
     * 
     * @var bool
     */
    protected $useBankSlip = false;

    /**
     * The subscription price to override the plan price.
     *
     * @var int
     */
    protected $customAmount = null;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected $couponCode = null;

    /**
     * SubscriptionBuilder constructor.
     * 
     * @param $billable
     * @param $planName
     */
    public function __construct($billable, $planName)
    {
        $this->billable = $billable;
        $this->planName = $planName;
    }

    /**
     * Specify a custom price for the subscription (override plan price).
     *
     * @param int $customAmount
     *
     * @return $this
     */
    public function withCustomAmount($customAmount)
    {
        $this->customAmount = $customAmount;

        return $this;
    }

    /**
     * Enable Bank Slip (Boleto) as the payment method.
     *
     * @return $this
     */
    public function withBankSlip()
    {
        $this->useBankSlip = true;

        return $this;
    }

    /**
     * The Coupon to apply into a new subscription.
     * 
     * @param $couponCode
     *
     * @return $this
     */
    public function withCoupon($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    /**
     * 
     */
    public function create()
    {
        $driver = app('caixeiro.driver');
        $driver->createSubscription($this->billable, $this);
    }

    public function shouldUseBankSlip()
    {
        return $this->useBankSlip;
    }

    public function hasCoupon()
    {
        return (bool) $this->couponCode;
    }

    public function getCouponCode()
    {
        return $this->couponCode;
    }

    public function getPlanName()
    {
        return $this->planName;
    }

    public function hasCustomAmount()
    {
        return (bool) $this->customAmount;
    }

    public function getCustomAmount()
    {
        return $this->customAmount;
    }
}
