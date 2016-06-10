<?php

namespace Artesaos\Caixeiro\Builders;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Illuminate\Database\Eloquent\Model;

/**
 * SubscriptionBuilder.
 * 
 * Responsible for preparing the information that builds a subscription.
 * Currently requires a plan name, and optionally,
 * bank slip as payment method,
 * custom subscription amount and
 * discount code (if supported by the driver).
 */
class SubscriptionBuilder
{
    /**
     * @var Model The billable model that owns the subscription.
     */
    protected $billable;

    /**
     * @var string The name of the plan being subscribed to
     */
    protected $planName;

    /**
     * @var bool Bank slip as payment method, only if supported by the plan
     */
    protected $useBankSlip = false;

    /**
     * @var int The subscription price in cents to override the plan price.
     */
    protected $customAmount = null;

    /**
     * @var string The coupon code being applied to the customer.
     */
    protected $couponCode = null;

    /**
     * SubscriptionBuilder Constructor.
     * 
     * @param Model $billable The billable instance. 
     * @param string $planName The desired plan name to subscribe under. 
     */
    public function __construct(Model $billable, $planName)
    {
        // set the billable instance and plan name
        // into the builder scope.
        $this->billable = $billable;
        $this->planName = $planName;
    }

    /**
     * Specify a custom price for the subscription (override plan price).
     *
     * @param int $customAmount The custom subscription pice.
     *
     * @return SubscriptionBuilder $this
     */
    public function withCustomAmount($customAmount)
    {
        $this->customAmount = $customAmount;

        return $this;
    }

    /**
     * Enable Bank Slip (Boleto) as the payment method.
     *
     * @return SubscriptionBuilder $this
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
     * Created the subscription into the enabled payment gateway
     * using the information build on the class instance.
     */
    public function create()
    {
        /** @var Driver $driver */
        $driver = app('caixeiro.driver');
        $driver->createSubscription($this);
    }

    /**
     * Method to set bank slip (boleto) as default payment method.
     * @return SubscriptionBuilder bool
     */
    public function shouldUseBankSlip()
    {
        return $this->useBankSlip;
    }

    /**
     * Is a coupon informed?
     *
     * @return bool
     */
    public function hasCoupon()
    {
        return (bool) $this->couponCode;
    }

    /**
     * Returns the coupon code, if any.
     *
     * @return string
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * The desired plan name.
     *
     * @return string
     */
    public function getPlanName()
    {
        return $this->planName;
    }

    /**
     * Is a custom price informed?
     *
     * @return bool
     */
    public function hasCustomAmount()
    {
        return (bool) $this->customAmount;
    }

    /**
     * Returns the custom price, if set.
     *
     * @return int
     */
    public function getCustomAmount()
    {
        return $this->customAmount;
    }

    /**
     * Returns configured the billable instance.
     *
     * @return Model The billable instance.
     */
    public function getBillable()
    {
        return $this->billable;
    }
}
