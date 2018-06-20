# laravel-google-authenticator

Google Authenticator implementation for Laravel

The package has a trait and a custom validation rule that you can use on any model for verifying the Google Authenticators code. 

## Instalation

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
 * A setter (mutator) for **enable_2fa** attribute
 * function **getQRCodeURL()** - gets the URL for retrieving the QR code image for the User to scan with Google Authenticator
 * function **getURIEncodedQRImage()** - gets the base64 encoded image (convenient for placing into <img src="..."> for emails and such...)
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

For MySQL databases you will need to add these attributes to your Models migration:

```php
$table->boolean('activated_2fa'); // you can index it if needed
$table->integer('attempted_2fa')->unsigned()->default(3);
$table->string('secret');
```

You can add some helper attributes to your model, for the sake of convenience:

```php

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'enable_2fa'];

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
    protected $appends = ['id', 'enabled_2fa'];

```

We've added ```enable_2fa``` to the **$fillable** attribute. Whenever the ```enable_2fa``` attribute is set to true on the User model, the Trait will generate a code for the model. You can do this manually.

We've also added ```enabled_2fa``` and ```activated_2fa``` to the **$visible** attribute, tho this is not necessary either. 

That's it! :)

### Displaying the QR code

Now you can show the QR code to the user either with:

```html
<img src="{{ $user->getQRCodeURL() }}">
```

or 

```html
<img src="{{ $user->getURIEncodedQRImage() }}">
```

### Verifying that the User set up Google Authenticator

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

### Adding 2FA custom Validation

You can easily verify the code on any custom request by adding the validation:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomRequest extends FormRequest
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

### Adding 2FA custom Validation using route model binding

Or you can leverage route model binding and create a validation in your custom Request model:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserDetails extends FormRequest
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
            'code' => 'required|2fa:admin'
        ];
    }
}
```

In this case the validator expects to find an ```admin``` object in the route. The object needs to implement the above mentioned trait in order for the validation to work. 