# Introduction
The hCaptcha is a human-computer verification solution that replaces Google reCaptcha. It has high performance, high availability and high recognition, especially suitable for regions with poor network quality, such as East Asia, Middle East, etc. Many well-known companies are using the hCaptcha solution.


The package is one of the recommended package on [the official developer guide of HCaptcha](https://github.com/hCaptcha/hcaptcha-integrations-list#laravel). No malicious code, ensuring the security of your development supply chain.

- Purchase a [VPS](https://bwh88.net/aff.php?aff=20075) or [Akamai VPS](https://www.linode.com/lp/refer/?r=2d4a0088743a2a06e3405514d486b8966c51a439) for developing and deploying applications.

## Installation

```
composer require scyllaly/hcaptcha
```

## Laravel 5 and above

### Setup

In `app/config/app.php` add the following :

Step 1: The ServiceProvider to the providers array :

```php
Scyllaly\HCaptcha\HCaptchaServiceProvider::class,
```

Step 2: The class alias to the aliases array :

```php
'HCaptcha' => Scyllaly\HCaptcha\Facades\HCaptcha::class,
```

Step 3: Publish the config file

```Shell
php artisan vendor:publish --provider="Scyllaly\HCaptcha\HCaptchaServiceProvider"
```

### Configuration

Add `HCAPTCHA_SECRET`, `HCAPTCHA_SITEKEY` and `HCAPTCHA_ENABLED` in **.env** file :

```
HCAPTCHA_SECRET=secret-key
HCAPTCHA_SITEKEY=site-key
HCAPTCHA_ENABLED=true
```

(You can obtain them from [Official Developer Guide](https://docs.hcaptcha.com/api#getapikey))

- Tips: If you do not have an account, please [sign up](https://hCaptcha.com/?r=d315c350eeee) it first.

### Usage

#### Init js source

With default options :

```php
 {!! HCaptcha::renderJs() !!}
```

With [language support](https://docs.hcaptcha.com/configuration) or [onloadCallback](https://docs.hcaptcha.com/configuration) option :

```php
 {!! HCaptcha::renderJs('fr', true, 'hcaptchaCallback') !!}
```

#### Display hCaptcha

Default widget :

```php
{!! HCaptcha::display() !!}
```

With [custom attributes](https://docs.hcaptcha.com/configuration#themes) (theme, size, callback ...) :

```php
{!! HCaptcha::display(['data-theme' => 'dark']) !!}
```

Invisible hCaptcha using a [submit button](https://docs.hcaptcha.com/configuration#themes):

```php
{!! HCaptcha::displaySubmit('my-form-id', 'submit now!', ['data-theme' => 'dark']) !!}
```
Notice that the id of the form is required in this method to let the autogenerated 
callback submit the form on a successful captcha verification.

#### Validation

There are two ways to apply HCaptcha validation to your form:

#### 1. Basic Approach

This method always applies the HCaptcha validation rule.

```php
$validate = Validator::make(Input::all(), [
    'h-captcha-response' => 'required|HCaptcha'
]);

```

In this approach, the `h-captcha-response` field is required and validated using the `HCaptcha` rule without any conditions.

#### 2. Conditional Approach

This method applies the HCaptcha validation rule only if the `HCAPTCHA_ENABLED` environment variable is set to `true`.

```php
$isHcaptchaEnabled = env('HCAPTCHA_ENABLED');
$rules = [
    // Other validation rules...
];

if ($isHcaptchaEnabled) {
    $rules['h-captcha-response'] = 'required|HCaptcha';
}

$request->validate($rules);

```

In this approach, the `h-captcha-response` field will be required and validated using the `HCaptcha` rule only when `HCAPTCHA_ENABLED` is set to `true`. This adds flexibility to your validation logic, allowing you to enable or disable HCaptcha validation as needed.

##### Custom Validation Message

Add the following values to the `custom` array in the `validation` language file :

```php
'custom' => [
    'h-captcha-response' => [
        'required' => 'Please verify that you are not a robot.',
        'h_captcha' => 'Captcha error! try again later or contact site admin.',
    ],
],
```

Then check for captcha errors in the `Form` :

```php
@if ($errors->has('h-captcha-response'))
    <span class="help-block">
        <strong>{{ $errors->first('h-captcha-response') }}</strong>
    </span>
@endif
```

### Testing

When using the [Laravel Testing functionality](http://laravel.com/docs/5.5/testing), you will need to mock out the response for the captcha form element.

So for any form tests involving the captcha, you can do this by mocking the facade behavior:

```php
// prevent validation error on captcha
HCaptcha::shouldReceive('verifyResponse')
    ->once()
    ->andReturn(true);

// provide hidden input for your 'required' validation
HCaptcha::shouldReceive('display')
    ->zeroOrMoreTimes()
    ->andReturn('<input type="hidden" name="h-captcha-response" value="1" />');
```

You can then test the remainder of your form as normal.

When using HTTP tests you can add the `h-captcha-response` to the request body for the 'required' validation:

```php
// prevent validation error on captcha
HCaptcha::shouldReceive('verifyResponse')
    ->once()
    ->andReturn(true);

// POST request, with request body including `h-captcha-response`
$response = $this->json('POST', '/register', [
    'h-captcha-response' => '1',
    'name' => 'Scyllaly',
    'email' => 'Scyllaly@example.com',
    'password' => '123456',
    'password_confirmation' => '123456',
]);
```

## Without Laravel

Checkout example below:

```php
<?php

require_once "vendor/autoload.php";

$secret  = 'CAPTCHA-SECRET';
$sitekey = 'CAPTCHA-SITEKEY';
$captcha = new \Scyllaly\HCaptcha\HCaptcha($secret, $sitekey);

if (! empty($_POST)) {
    var_dump($captcha->verifyResponse($_POST['h-captcha-response']));
    exit();
}

?>

<form action="?" method="POST">
    <?php echo $captcha->display(); ?>
    <button type="submit">Submit</button>
</form>

<?php echo $captcha->renderJs(); ?>
```
