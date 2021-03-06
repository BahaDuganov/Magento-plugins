## Synopsis
An extension to add integration with Payment Gateway.
This payment method can be restricted to work only with specific Shipping method.

## Motivation
This is one of a collection of examples to demonstrate the features of Magento 2.  The intent of this sample is to demonstrate how to create own Payment Gateway integration

## Technical feature

### Module configuration
1. Package details [composer.json](composer.json).
2. Module configuration details (sequence) in [module.xml](etc/module.xml).
3. Module configuration available through Stores->Configuration [system.xml](etc/adminhtml/system.xml)

Payment gateway module depends on `Sales`, `Payment` and `Checkout` Magento modules.
For more module configuration details, please look through [module development docs](http://devdocs.magento.com/guides/v2.0/extension-dev-guide/module-load-order.html).

### Gateway configuration
On the next step, we might specify gateway domain configuration in [config.xml](etc/config.xml).

##### Let's look into configuration attributes:
 * <code>debug</code> enables debug mode by default, e.g log for request/response
 * <code>active</code> is payment active by default
 * <code>model</code> `Payment Method Facade` used for integration with `Sales` and `Checkout` modules
 * <code>merchant_gateway_key</code> encrypted merchant credential
 * <code>order_status</code> default order status
 * <code>payment_action</code> default action of payment
 * <code>title</code> default title for a payment method
 * <code>currency</code> supported currency
 * <code>can_authorize</code> whether payment method supports authorization
 * <code>can_capture</code> whether payment method supports capture
 * <code>can_void</code> whether payment method supports void
 * <code>can_use_checkout</code> checkout availability
 * <code>is_gateway</code> is an integration with gateway
 * <code>sort_order</code> payment method order position on checkout/system configuration pages
 * <code>debugReplaceKeys</code> request/response fields, which will be masked in log
 * <code>paymentInfoKeys</code> transaction request/response fields displayed on payment information block
 * <code>privateInfoKeys</code> paymentInfoKeys fields which should not be displayed in customer payment information block

### Dependency Injection configuration
> To get more details about dependency injection configuration in Magento 2, please see [DI docs](http://devdocs.magento.com/guides/v2.0/extension-dev-guide/depend-inj.html).

In a case of Payment Gateway, DI configuration is used to define pools of `Gateway Commands` with related infrastructure and to configure `Payment Method Facade` (used by `Sales` and `Checkout` modules to perform commands)

Payment Method Facade configuration:
```xml
<!-- Payment Method Facade configuration -->
<virtualType name="MyfatoorahFacade" type="Magento\Payment\Model\Method\Adapter">
    <arguments>
        <argument name="code" xsi:type="const">\MyFatoorah\Myfatoorah\Model\Ui\ConfigProvider::CODE</argument>
        <argument name="formBlockType" xsi:type="string">Magento\Payment\Block\Form</argument>
        <argument name="infoBlockType" xsi:type="string">MyFatoorah\Myfatoorah\Block\Info</argument>
        <argument name="valueHandlerPool" xsi:type="object">MyfatoorahValueHandlerPool</argument>
        <argument name="commandPool" xsi:type="object">MyfatoorahCommandPool</argument>
    </arguments>
</virtualType>
```
 * <code>code</code> Payment Method code
 * <code>formBlockType</code> Block class name, responsible for Payment Gateway Form rendering on a checkout.
  Since Opepage Checkout uses knockout.js for rendering, this renderer is used only during Checkout process from Admin panel.
 * <code>infoBlockType</code> Block class name, responsible for Transaction/Payment Information details rendering in Order block.
 * <code>valueHandlerPool</code> Value handler pool used for queries to configuration
 * <code>commandPool</code> Pool of Payment Gateway commands


#### Pools
> ! All `Payment\Gateway` provided pools implementations use lazy loading for components, i.e configured with component type name instead of component object

##### Value Handlers
There should be at least one Value Handler with `default` key provided for ValueHandlerPool.

```xml
!-- Value handlers infrastructure -->
<virtualType name="MyfatoorahValueHandlerPool" type="Magento\Payment\Gateway\Config\ValueHandlerPool">
    <arguments>
        <argument name="handlers" xsi:type="array">
            <item name="default" xsi:type="string">MyfatoorahConfigValueHandler</item>
        </argument>
    </arguments>
</virtualType>
<virtualType name="MyfatoorahConfigValueHandler" type="Magento\Payment\Gateway\Config\ConfigValueHandler">
    <arguments>
        <argument name="configInterface" xsi:type="object">MyfatoorahConfig</argument>
    </arguments>
</virtualType>
```

##### Commands
All gateway commands should be added to CommandPool instance
```xml
<!-- Commands infrastructure -->
<virtualType name="MyfatoorahCommandPool" type="Magento\Payment\Gateway\Command\CommandPool">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="authorize" xsi:type="string">MyfatoorahAuthorizeCommand</item>
            <item name="capture" xsi:type="string">MyfatoorahCaptureCommand</item>
            <item name="void" xsi:type="string">MyfatoorahVoidCommand</item>
        </argument>
    </arguments>
</virtualType>
```

Example of Authorization command configuration:
```xml
<!-- Authorize command -->
<virtualType name="MyfatoorahAuthorizeCommand" type="Magento\Payment\Gateway\Command\GatewayCommand">
    <arguments>
        <argument name="requestBuilder" xsi:type="object">MyfatoorahAuthorizationRequest</argument>
        <argument name="handler" xsi:type="object">MyfatoorahResponseHandlerComposite</argument>
        <argument name="transferFactory" xsi:type="object">MyFatoorah\Myfatoorah\Gateway\Http\TransferFactory</argument>
        <argument name="client" xsi:type="object">MyFatoorah\Myfatoorah\Gateway\Http\Client\ClientMock</argument>
    </arguments>
</virtualType>

<!-- Authorization Request -->
<virtualType name="MyfatoorahAuthorizationRequest" type="Magento\Payment\Gateway\Request\BuilderComposite">
    <arguments>
        <argument name="builders" xsi:type="array">
            <item name="transaction" xsi:type="string">MyFatoorah\Myfatoorah\Gateway\Request\AuthorizationRequest</item>
            <item name="mockData" xsi:type="string">MyFatoorah\Myfatoorah\Gateway\Request\MockDataRequest</item>
        </argument>
    </arguments>
</virtualType>
<type name="MyFatoorah\Myfatoorah\Gateway\Request\AuthorizationRequest">
    <arguments>
        <argument name="config" xsi:type="object">MyfatoorahConfig</argument>
    </arguments>
</type>

<!-- Response handlers -->
<virtualType name="MyfatoorahResponseHandlerComposite" type="Magento\Payment\Gateway\Response\HandlerChain">
    <arguments>
        <argument name="handlers" xsi:type="array">
            <item name="txnid" xsi:type="string">MyFatoorah\Myfatoorah\Gateway\Response\TxnIdHandler</item>
            <item name="fraud" xsi:type="string">MyFatoorah\Myfatoorah\Gateway\Response\FraudHandler</item>
        </argument>
    </arguments>
</virtualType>
```
* `MyfatoorahAuthorizeCommand` - instance of GatewayCommand provided by `Payment\Gateway` configured with request builders, response handlers and transfer client
* `MyfatoorahAuthorizationRequest` - Composite of request parts used for Authorization
* `MyfatoorahResponseHandlerComposite` - Composite\List of response handlers.

## Installation
This module is intended to be installed using composer.  After including this component and enabling it, you can verify it is installed by going the backend at:
STORES -> Configuration -> ADVANCED/Advanced ->  Disable Modules Output
Once there check that the module name shows up in the list to confirm that it was installed correctly.

## Tests
Unit tests could be found in the [Test/Unit](Test/Unit) directory.

## Contributors
Magento Core team

## License
[Open Source License](LICENSE.txt)