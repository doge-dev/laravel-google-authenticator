<?php

namespace DogeDev\GoogleAuthenticator\Traits;

use App\Facades\GoogleAuthenticatorFacade;
use GuzzleHttp\Client;

/**
 * Trait Verifies2FACode
 *
 * Generates a GoogleAuthenticator secret if the secret attribute is set during object creation.
 * Contains methods for outputting the QR code verifying the user submitted code (authorising action).
 *
 * @package App\Traits
 */
trait Verifies2FACode
{
    /**
     * Set the secret attribute.
     *
     * @param $secret
     * @return void
     */
    public function setEnable2faAttribute($secret)
    {
        if (!$secret) {

            return;
        }

        $this->attributes['secret']        = GoogleAuthenticatorFacade::createSecret();
        $this->attributes['activated_2fa'] = false;
        $this->attributes['attempted_2fa'] = 3;

        $this->activated_2fa = false;
        $this->attempted_2fa = 3;
    }

    /**
     * Get the two_factor_authentication_enabled attribute
     *
     * @return bool
     */
    public function getEnabled2faAttribute()
    {
        return !empty($this->secret);
    }

    /**
     * Returns a QR code for Google Authenticator
     *
     * @return mixed
     */
    public function getQRCodeURL()
    {
        if (!$this->secret) {

            return true;
        }

        return GoogleAuthenticatorFacade::getQRCodeGoogleUrl($this->getNameForQRCode(), $this->secret);
    }

    /**
     * Gets the QR Image URI encoded
     *
     * @return string
     */
    public function getURIEncodedQRImage()
    {
        $client = new Client();

        $response = $client->request('GET', $this->getQRCodeURL());

        $type = $response->getHeader('content-type')[0];

        $data = base64_encode($response->getBody());

        return "data:$type;base64,$data";
    }

    /**
     * Verifies the code generated by Google Authenticator
     *
     * @param null $code
     * @return bool
     */
    public function verifyCode($code = null)
    {
        if (!$this->secret) {

            return true;
        }

        if (empty($this->attempted_2fa)) {

            return false;
        }

        $verified = GoogleAuthenticatorFacade::verifyCode($this->secret, $code, 0);

        dd($verified);

        if ($verified) {

            $this->attempted_2fa = 3;

        } else {

            $this->attempted_2fa--;
        }

        $this->save();

        return $verified;
    }

    /**
     * Activates 2FA on the model
     *
     * @param null $code
     * @return $this
     */
    public function activate2FA($code = null)
    {
        $this->activated_2fa = $this->verifyCode($code);

        return $this;
    }

    /**
     * Gets the name to be displayed in Google Authenticator
     *
     * @return string
     */
    public function getNameForQRCode()
    {
        return env('APP_NAME');
    }
}