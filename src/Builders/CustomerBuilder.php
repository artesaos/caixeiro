<?php

namespace Artesaos\Caixeiro\Builders;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Illuminate\Database\Eloquent\Model;

/**s
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
     * @var bool Is a address informed;
     */
    protected $addressPresent = false;

    /**
     * @var bool Is a Phone Number present.
     */
    protected $phoneNumberPresent = false;

    /**
     * @var string Customer official document, like CPF, if any.
     */
    protected $document = null;

    /**
     * @var array The credit card data.
     */
    protected $cardData = [
        'holder_name'       =>  null,
        'number'            =>  null,
        'expiration_month'  =>  null,
        'expiration_year'   =>  null,
        'verification_code' =>  null,
    ];

    /**
     * @var array Customer Address, if any.
     */
    protected $address = [
        'street'        =>  null,
        'number'        =>  null,
        'complement'    =>  null,
        'district'      =>  null,
        'city'          =>  null,
        'state'         =>  null,
        'country'       =>  null,
        'zip'           =>  null
    ];

    /**
     * @var array Customer Phone Number, if any.
     */
    protected $phoneNumber = [
        'area'      =>  null,
        'number'    =>  null,
    ];

    /**
     * @var string Customer birthday, if any.
     */
    protected $birthday = null;

    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var string
     */
    protected $email = null;

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
     * @param string $street
     * @param string $number
     * @param string $complement
     * @param string $district
     * @param string $city
     * @param string $state
     * @param string $country
     * @param string $zipCode
     *
     * @return $this
     */
    public function withAddress($street, $number, $complement, $district, $city, $state, $country, $zipCode)
    {
        $this->addressPresent = true;
        
        $this->address = [
            'street'        =>  $street,
            'number'        =>  $number,
            'complement'    =>  $complement,
            'district'      =>  $district,
            'city'          =>  $city,
            'state'         =>  $state,
            'country'       =>  $country,
            'zip'           =>  $zipCode
        ];

        return $this;
    }

    /**
     * @param string $document Official document number.
     *
     * @return $this
     */
    public function withDocument($document)
    {
        $this->document = $document;
        
        return $this;
    }

    /**
     * @param string $areaCode
     * @param string $number
     * 
     * @return $this
     */
    public function withPhoneNumber($areaCode, $number)
    {
        $this->phoneNumberPresent = true;
        
        $this->phoneNumber = [
            'area' => $areaCode,
            'number' => $number
        ];
        
        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function withEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function withName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function withBirthday($birthday)
    {
        $this->birthday = $birthday;

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
    
    public function update()
    {
        // detects the driver
        /** @var Driver $driver */
        $driver = app('caixeiro.driver');

        // call the driver customer preparation passing the billable instance and
        // the CustomerBuilder itself.
        return $driver->updateCustomer($this);
    }

    /**
     * @return bool Is a card present? (public access)
     */
    public function cardPresent()
    {
        return $this->cardPresent;
    }

    /**
     * @return bool Is an address present? (public access)
     */
    public function addressPresent()
    {
        return $this->addressPresent;
    }

    /**
     * @return bool Is a phone number present? (public access)
     */
    public function phoneNumberPresent()
    {
        return $this->phoneNumberPresent;
    }

    /**
     * @return Model The billable instance.
     */
    public function getBillable()
    {
        return $this->billable;
    }

    /**
     * @return array Returns the card data, if any.
     */
    public function getCardData()
    {
        return $this->cardData;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getBirthday()
    {
        return $this->birthday;
    }
}
