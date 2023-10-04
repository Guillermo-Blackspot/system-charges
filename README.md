## TRAIT: HasSystemChargesIntegration (For subsidiaries)

```php

$subsidiary = Subsidiary::with('service_integrations')->first();

//

$subsidiary->systemCharges->getService();
$subsidiary->systemCharges->assertServiceIsActive();
$subsidiary->systemCharges->assertServiceExists();

//
$amount   = 1400;
$options  = [
    'metadata' => []
];

$subsidiary->systemCharges->createWireTransfer($customer, $amount, $options);
$subsidiary->systemCharges->createPaymentInSubsidiary($customer, $amount, $options);            
$subsidiary->systemCharges->createAgreement($customer, $amount, $options);
$subsidiary->systemCharges->createWith($customer, $paymentMethod , $amount, $options);
$subsidiary->systemCharges->createPaymentIntent($customer, $amount, $options);

```

## TRAIT: BillableForSystemCharges (For customers)

```php

    $subsidiary  = Subsidiary::with('service_integrations')->first();
    $newCustomer = User::first(); 

    $newCustomer->usingSystemChargesService($subsidiary->getService())
                ->
                getService()
                assertServiceIsActive()
                assertServiceExists()
                createWireTransfer(...)
                createPaymentInSubsidiary(...)
                createAgreement(...)
                createWith(...)
                createPaymentIntent();


    /**
     * Get the system_payment_intents, eloquent relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function system_payment_intents() : return relationship;

```