![build](https://github.com/coddin-web/magento2-cartbridge/actions/workflows/main.yml/badge.svg?event=push)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![codecov](https://codecov.io/gh/coddin-web/magento2-cartbridge/branch/main/graph/badge.svg?token=M03T8HVKNW)](https://codecov.io/gh/coddin-web/magento2-cartbridge)

# Magento 2 CartBridge
A module to easily add items to an existing Guest/Customer cart programmatically

The only dependency it has is: it has to know about the current Session, either by already being in a programmatic environment that is aware of the current session, or by an AJAX call that has the xhrClientCredentials added to the call

e.g. (jQuery)
```
xhrFields: {
    withCredentials: true
},
```

### Installation

```shell
$ composer require coddin/magento2-cartbridge
```

### Testing

The tests can be run within a Magento 2 instance by using this command:

```
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/coddin/magento2-cartbridge/Test/Unit
```
