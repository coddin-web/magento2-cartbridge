# Magento 2 CartBridge
A module to easily add items to an existing Guest/Customer cart programmatically

The only dependency it has is: it has to know about the current Session, either by already being in a programmatic environment that is aware of the current session, or by an AJAX call that has the xhrClientCredentials added to the call

e.g. (jQuery)
```
xhrFields: {
    withCredentials: true
},
```

Also, as an example / useful endpoint, a "getCartId" frontname has been added to get the current Cart/QuoteID for the Guest/Customer
