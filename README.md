# laravel-sms-verification
Library for sending out and verifying SMS codes using AWS SNS

The package has a trait and a custom validation rule that you can use on any model for verifying with an SMS code.

## Table of contents

* [Installation](#installation)
* [Example](#example)
* [Sending out the SMS code](#sending-out-the-sms-code)
* [Verifying the SMS code](#verifying-the-sms-code)
* [Setting SMS verification attempt limits](#setting-sms-verification-attempt-limits)
* [Adding custom Validation for verifying an SMS code](#adding-custom-validation-for-verifying-an-sms-code)
* [Adding custom Validation for verifying an SMS code using route model binding](#adding-custom-validation-for-verifying-an-sms-code-using-route-model-binding)
* [Changing the SMS message being sent](#changing-the-sms-message-being-sent)
* [Changing the SMS sender](#changing-the-sms-sender)

## Installation

Pull the lib with composer:

```bash
composer require doge-dev/laravel-sms-verification
```

Add the service provider in ```config/app.php```

```php
DogeDev\SMSVerification\SMSVerificationServiceProvider::class,
```

You can add SMS Verification to any model, and it will create:

* a function **setSMSVerificationNumber($mobile)** - sets the mobile number and sends out the SMS message containg the verification code
* a function **verifySMSCode($code)** -verifies the SMS code 
* and a bunch of private methods that are used for verifying the SMS code. You can check them out in the Trait itself for more details.

## Example

Add the ```VerifiesSMSCode``` trait to your User model (or any other model on which you might want to enable 2FA):

```php
<?php

namespace App;

use DogeDev\SMSVerification\Traits\VerifiesSMSCode;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, VerifiesSMSCode;

    ...
```

For MySQL databases you will need to add these attributes to your Model's migration:

```php
$table->string('sms_verification_number')->default(''); // you can index this if you want
$table->string('sms_verification_code')->default('');
$table->boolean('sms_verification_status')->default(false);
```

That's it! :) Now you can send out SMS codes and verify them later.

## Sending out the SMS code

Send an SMS by setting the SMS verification number attribute:

```php
$user->setSMSVerificationNumber($number)
```

This will send an sms message to the given ```$number```. If the message fails to send an Expception will pop up. If SMS sending succeeds, the ```sms_verification_number```, ```sms_verification_code``` and ```sms_verification_status``` attributes will be set on the model.

After this, you can verify the code at any time.

## Verifying the SMS code

For simple verification you can use:

```php
$user->verifySMSCode($request->get('code'));
```

This will set the ```sms_verification_status``` attribute to **true**. If attempt limiting is enabled, this will increment the attempts that the User had. Once this limit is exceeded a TooManySMSVerificationAttempts Exception will be thrown.

## Setting SMS verification attempt limits

You can adjust the number of attempts a User can have at verifying the code by overwriting the ```$sms_verification_attempt_limit``` on the model implementing the trait:

```php
<?php

namespace App;

use DogeDev\SMSVerification\Traits\VerifiesSMSCode;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, VerifiesSMSCode;
    
    protected $sms_verification_attempt_limit = 10;

    ...
```

This value defaults to 5.

If you don't want to limit your users with attempts, set this variable to 0.

## Adding custom Validation for verifying an SMS code

You can easily verify the code on any custom request by adding the validation:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SomeCustomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required|sms-code'
        ];
    }
}
```

The validator will try to validate the code using the logged in user (Auth::user()).

## Adding custom Validation for verifying an SMS code using route model binding

Or you can leverage route model binding and create a validation in your custom Request model:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountDetails extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required|sms-code:account'
        ];
    }
}
```

In this case the validator expects to find an ```account``` object in the route. The object needs to implement the above mentioned Trait in order for the validation to work. 


## Changing the SMS message being sent

You can set the text that is sent via SMS by overriding the traits default **getSMSVerificationMessage($code)** function in your Model implementation:

```php
class User extends Model
{

    ...

    /**
     * Gets the message to be sent with the SMS
     *
     * @param $code
     * @return string
     */
    public function getSMSVerificationMessage($code)
    {
        return "Here is your code for " . env("APP_NAME") . ": " . $code;
    }
    
    ...
}

```

## Changing the SMS sender

You can set the sender by overriding the traits default **getSMSVerificationMessage($code)** function in your Model implementation:

```php
class User extends Model
{

    ...

    /**
     * Gets the sender of the verification SMS
     *
     * @return string
     */
    public function getSMSVerificationSender()
    {
        return "CustomName";
    }
    
    ...
}

```