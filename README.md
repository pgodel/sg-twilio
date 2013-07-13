sg-twilio
=========

Twilio integration with ServerGrove Control Panel to control a server. It allows to control a server through a phone call.

This app has been created during the South Florida PHP Usergroup Hackaton as a demo app to integrate Twilio and the
ServerGrove Control Panel API.

Installation
------------

The installation is done with Composer:

    # install composer
    curl -sS https://getcomposer.org/installer | php

    # install dependencies
    php composer.phar install

Then, configure your virtual host to to the web directory.

Configuration
-------------

sg-twilio uses Twilio API to make phone calls.

A config/config.yml configuration file includes the parameters needed to run the app.

    sg_api:
      key: your_api_key
      secret: your_api_secret

    twilio:
      number: twilio_number
      sid: twilio_sid
      token: twilio_token

the sg_api key and secret can be generated in the User profile section in the ServerGrove Control Panel.