<?php

use Scyllaly\HCaptcha\HCaptcha;

class HCaptchaTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HCaptchaTest
     */
    private $captcha;

    public function setUp()
    {
        parent::setUp();
        $this->captcha = new hCaptcha('{secret-key}', '{site-key}');
    }

    public function testJsLink()
    {
        $this->assertTrue($this->captcha instanceof hCaptcha);

        $simple = '<script src="https://hcaptcha.com/1/api.js?" async defer></script>' . "\n";
        $withLang = '<script src="https://hcaptcha.com/1/api.js?hl=vi" async defer></script>' . "\n";
        $withCallback = '<script src="https://hcaptcha.com/1/api.js?render=explicit&onload=myOnloadCallback" async defer></script>' . "\n";

        $this->assertEquals($simple, $this->captcha->renderJs());
        $this->assertEquals($withLang, $this->captcha->renderJs('vi'));
        $this->assertEquals($withCallback, $this->captcha->renderJs(null, true, 'myOnloadCallback'));
    }

    public function testDisplay()
    {
        $this->assertTrue($this->captcha instanceof hCaptcha);

        $simple = '<div data-sitekey="{site-key}" class="h-captcha"></div>';
        $withAttrs = '<div data-theme="light" data-sitekey="{site-key}" class="h-captcha"></div>';

        $this->assertEquals($simple, $this->captcha->display());
        $this->assertEquals($withAttrs, $this->captcha->display(['data-theme' => 'light']));
    }

    public function testdisplaySubmit()
    {
        $this->assertTrue($this->captcha instanceof hCaptcha);

        $javascript = '<script>function onSubmittest(){document.getElementById("test").submit();}</script>';
        $simple = '<button data-callback="onSubmittest" data-sitekey="{site-key}" class="h-captcha"><span>submit</span></button>';
        $withAttrs = '<button data-theme="light" class="h-captcha 123" data-callback="onSubmittest" data-sitekey="{site-key}"><span>submit123</span></button>';

        $this->assertEquals($simple . $javascript, $this->captcha->displaySubmit('test'));
        $withAttrsResult = $this->captcha->displaySubmit('test', 'submit123', ['data-theme' => 'light', 'class' => '123']);
        $this->assertEquals($withAttrs . $javascript, $withAttrsResult);
    }

    public function testdisplaySubmitWithCustomCallback()
    {
        $this->assertTrue($this->captcha instanceof hCaptcha);

        $withAttrs = '<button data-theme="light" class="h-captcha 123" data-callback="onSubmitCustomCallback" data-sitekey="{site-key}"><span>submit123</span></button>';

        $withAttrsResult = $this->captcha->displaySubmit('test-custom', 'submit123', ['data-theme' => 'light', 'class' => '123', 'data-callback' => 'onSubmitCustomCallback']);
        $this->assertEquals($withAttrs, $withAttrsResult);
    }
}
