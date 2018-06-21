# laravel-google-authenticator

Google Authenticator implementation for Laravel

The package has a trait and a custom validation rule that you can use on any model for verifying the Google Authenticators code.

## Table of contents

* [Installation](#installation)
* [Example](#example)
* [Displaying the QR code](#displaying-the-qr-code)
* [Verifying that the User set up Google Authenticator](#verifying-that-the-user-set-up-google-authenticator)
* [Adding 2FA custom Validation](#adding-2fa-custom-validation)
* [Adding 2FA custom Validation using route model binding](#adding-2fa-custom-validation-using-route-model-binding)
* [Adding a custom name in Google Authenticator](#adding-a-custom-name-in-google-authenticator)

## Installation

Pull the lib with composer:

```bash
composer require doge-dev/laravel-google-authenticator
```

Add the service provider in ```config/app.php```

```php
DogeDev\GoogleAuthenticator\GoogleAuthenticatorServiceProvider::class,
```

You can add 2 Factor Verification to any model, and it will create:

 * A getter (accessor) for **enabled_2fa** attribute
 * function **enable2FA()** - enables 2 Factor Authentication on the model.
 * function **getQRCodeURL()** - gets the URL for retrieving the QR code image for the User to scan with Google Authenticator
 * function **getBase64EncodedQRImage()** - gets the base64 encoded image (convenient for placing into <img src="..."> for emails and such...)
 * function **verifyCode($code)** - attempts to verify the code submitted by the user (will increment the attempts)
 * function **activate2FA($code)** - attempts to verify the code and on successful verification will set the ```activated_2fa``` attribute to true
 * function **getNameForQRCode()** -  function that is used for generating a name that will be displayed in the Google Authenticator App (you can overwrite this function to use a custom naming convention)

## Example

Add the ```Verifies2FACode``` trait to your User model (or any other model on which you might want to enable 2FA):

```php
<?php

namespace App;

use DogeDev\GoogleAuthenticator\Traits\Verifies2FACode;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Verifies2FACode;

    ...
```

For MySQL databases you will need to add these attributes to your Model's migration:

```php
$table->boolean('activated_2fa')->default(false); // you can index it if needed
$table->string('secret')->nullable();
```

Call the ```enable2FA()``` function on your model, and you're done. This will generate a secret for the given model.

```php
$user->enable2FA();
```

You can add some helper attributes to your model, for the sake of convenience. We've also added ```enabled_2fa``` and ```activated_2fa``` to the **$visible** attribute, tho this is not necessary either. 

```php

    /**
     * The attributes that should be visible for arrays.
     *
     * @var array
     */
    protected $visible = ['name', 'enabled_2fa', 'activated_2fa'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['enabled_2fa'];

```

That's it! :) Now you can display the QR code to your user so that he can start using the Google Authenticator with your app.

## Displaying the QR code

Now you can show the QR code to the user either with:

```html
<img src="{{ $user->getQRCodeURL() }}">
```

or 

```html
<img src="{{ $user->getBase64EncodedQRImage() }}">
```

## Verifying that the User set up Google Authenticator

You can check if the User successfully activated his 2FA:

```php
$user->activated_2fa 
```

When a User scans the QR Code, prompt him for the code and validate the User's 2FA:

```php
$user->activate2FA($request->get('code'));
```

For simple verification you can use:

```php
$user->verifyCode($request->get('code'));
```

## Adding 2FA custom Validation

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
            'code' => 'required|2fa'
        ];
    }
}
```

The validator will try to validate the code using the logged in user (Auth::user()).

## Adding 2FA custom Validation using route model binding

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
            'code' => 'required|2fa:account'
        ];
    }
}
```

In this case the validator expects to find an ```account``` object in the route. The object needs to implement the above mentioned Trait in order for the validation to work. 


## Adding a custom name in Google Authenticator

You can set the text that is displayed in Google Authenticator by overriding the traits default **getNameForQRCode()** function in your Model implementation:

```php
class User extends Model
{

    ...

    /**
     * Gets the name to be displayed in Google Authenticator
     *
     * @return string
     */
    public function getNameForQRCode()
    {
        return env('APP_NAME') . "@" . $this->email;
    }
}

```