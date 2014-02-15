#!/bin/sh

sed "s/Checkout/\\\\modules\\\\checkout/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Payment - Test/\\\\modules\\\\payment_test/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Shipping - Flat Rate/\\\\modules\\\\shipping_flat_rate/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Login Sessions/\\\\modules\\\\login_session/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Subscriptions/\\\\modules\\\\subscriptions/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Block MathJax/\\\\modules\\\\block_mathjax/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Block Question/\\\\modules\\\\block_question/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Payment - PayPal/\\\\modules\\\\payment_paypal/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Products/\\\\modules\\\\products/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Data Feed - GetPrice/\\\\modules\\\\datafeed_getprice/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php

sed "s/Data Feed - MyShopping/\\\\modules\\\\datafeed_myshopping/g;" core/config/config.php > core/config/config.php.xx
mv core/config/config.php.xx core/config/config.php
