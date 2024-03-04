<?php

namespace Scyllaly\HCaptcha;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class HCaptcha
{
    const CLIENT_API = 'https://hcaptcha.com/1/api.js';
    const VERIFY_URL = 'https://hcaptcha.com/siteverify';

    /**
     * The hCaptcha secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The hCaptcha sitekey key.
     *
     * @var string
     */
    protected $sitekey;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * The cached verified responses.
     *
     * @var array
     */
    protected $verifiedResponses = [];
    
    /**
     * @var null
     * lastScore
     */
    protected $lastScore = null;
    
    
    /**
     * HCaptcha.
     *
     * @param string $secret
     * @param string $sitekey
     * @param array  $options
     */
    public function __construct($secret, $sitekey, $options = [])
    {
        $this->secret = $secret;
        $this->sitekey = $sitekey;
        $this->http = new Client($options);
    }

    /**
     * Render HTML captcha.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function display($attributes = [])
    {
        $attributes = $this->prepareAttributes($attributes);
        return '<div' . $this->buildAttributes($attributes) . '></div>';
    }

    /**
     * @see display()
     */
    public function displayWidget($attributes = [])
    {
        return $this->display($attributes);
    }

    /**
     * Display a Invisible hCaptcha by embedding a callback into a form submit button.
     *
     * @param string $formIdentifier the html ID of the form that should be submitted.
     * @param string $text           the text inside the form button
     * @param array  $attributes     array of additional html elements
     *
     * @return string
     */
    public function displaySubmit($formIdentifier, $text = 'submit', $attributes = [])
    {
        $javascript = '';
        if (!isset($attributes['data-callback'])) {
            $functionName = 'onSubmit' . str_replace(['-', '=', '\'', '"', '<', '>', '`'], '', $formIdentifier);
            $attributes['data-callback'] = $functionName;
            $javascript = sprintf(
                '<script>function %s(){document.getElementById("%s").submit();}</script>',
                $functionName,
                $formIdentifier
            );
        }

        $attributes = $this->prepareAttributes($attributes);

        $button = sprintf('<button%s><span>%s</span></button>', $this->buildAttributes($attributes), $text);

        return $button . $javascript;
    }

    /**
     * Render js source
     *
     * @param null   $lang
     * @param bool   $callback
     * @param string $onLoadClass
     *
     * @return string
     */
    public function renderJs($lang = null, $callback = false, $onLoadClass = 'onloadCallBack')
    {
        return '<script src="' . $this->getJsLink($lang, $callback, $onLoadClass) . '" async defer></script>' . "\n";
    }

    /**
     * Verify hCaptcha response.
     *
     * @param string $response
     * @param string $clientIp
     *
     * @return bool
     */
    public function verifyResponse($response, $clientIp = null)
    {
        if (empty($response)) {
            return false;
        }
        
        if (in_array($response, $this->verifiedResponses)) {
            return true;
        }
        
        $verifyResponse = $this->sendRequestVerify([
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $clientIp,
        ]);
        
        if (isset($verifyResponse['success']) && $verifyResponse['success']) {
            $this->verifiedResponses[] = $response;
            
            // Handle score if enabled and available
            $this->lastScore = isset($verifyResponse['score']) ? $verifyResponse['score'] : null;
            if ($this->isScoreVerificationEnabled()) {
                if (!isset($verifyResponse['score'])) {
                    throw new \RuntimeException('Score verification enabled but score is missing from the response.');
                }
                
                if (!$this->isScoreAcceptable($verifyResponse['score'])) {
                    return false;
                }
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Verify hCaptcha response by Symfony Request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequest(Request $request)
    {
        return $this->verifyResponse(
            $request->get('h-captcha-response'),
            $request->getClientIp()
        );
    }

    /**
     * Get hCaptcha js link.
     *
     * @param string  $lang
     * @param boolean $callback
     * @param string  $onLoadClass
     *
     * @return string
     */
    public function getJsLink($lang = null, $callback = false, $onLoadClass = 'onloadCallBack')
    {
        $client_api = static::CLIENT_API;
        $params = [];

        $callback ? $this->setCallBackParams($params, $onLoadClass) : false;
        $lang ? $params['hl'] = $lang : null;

        return $client_api . '?' . http_build_query($params);
    }
    
    /**
     * Check if the score is above the acceptable threshold.
     *
     * @param float $score
     * @return bool
     */
    protected function isScoreAcceptable($score)
    {
        return $score > config('HCaptcha.score_threshold', 0.7);
    }
    
    /**
     * Get the score from the last successful hCaptcha verification.
     *
     * @return float|null The score or null if not available.
     */
    public function getScoreFromLastVerification()
    {
        return $this->lastScore;
    }
    
    /**
     * Check if score verification is enabled.
     *
     * @return bool
     */
    protected function isScoreVerificationEnabled()
    {
        return config('HCaptcha.score_verification_enabled', false);
    }

    /**
     * @param $params
     * @param $onLoadClass
     */
    protected function setCallBackParams(&$params, $onLoadClass)
    {
        $params['render'] = 'explicit';
        $params['onload'] = $onLoadClass;
    }

    /**
     * Send verify request.
     *
     * @param array $query
     *
     * @return array
     */
    protected function sendRequestVerify(array $query = [])
    {
        $response = $this->http->request('POST', static::VERIFY_URL, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Prepare HTML attributes and assure that the correct classes and attributes for captcha are inserted.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function prepareAttributes(array $attributes)
    {
        $attributes['data-sitekey'] = $this->sitekey;
        if (!isset($attributes['class'])) {
            $attributes['class'] = '';
        }
        $attributes['class'] = trim('h-captcha ' . $attributes['class']);

        return $attributes;
    }

    /**
     * Build HTML attributes.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function buildAttributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key . '="' . $value . '"';
        }

        return count($html) ? ' ' . implode(' ', $html) : '';
    }
}
