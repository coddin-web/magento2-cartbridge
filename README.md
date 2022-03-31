# Magento 2 CartBridge
A module to easily add items to an existing Guest/Customer cart programmatically

The only dependency it has is: it has to know about the current Session, either by already being in a programmatic environment that is aware of the current session, or by an AJAX call that has the xhrClientCredentials added to the call

e.g. (jQuery)
```
xhrFields: {
    withCredentials: true
},
```

### Testing

The tests can be run within a Magento 2 instance by using this command:

```
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/coddin/magento2-cartbridge/Test/Unit
```
