<?php
namespace synacksa\casperjsphp;

/**
 * CasperJS wrapper
 *
 * Based on https://github.com/alwex/php-casperjs
 *
 * @author Garth Michel <syndrome@gmail.com>
 *
 */
class Casper
{
    const TAG_CURRENT_URL = '[CURRENT_URL]';
    const TAG_CURRENT_TITLE = '[CURRENT_TITLE]';
    const TAG_CURRENT_PAGE_CONTENT = '[CURRENT_PAGE_CONTENT]';
    const TAG_CURRENT_HTML = '[CURRENT_HTML]';

    private $_parts = [];
    private $_debug = false;
    private $_script = '';
    private $_output = [];
    private $_requestedUrls = [];
    private $_currentUrl = '';
    private $_userAgent = 'casper';
    // default viewport values
    private $_viewPortWidth = 1024;
    private $_viewPortHeight = 768;
    private $_current_page_content = '';
    private $_current_html = '';
    private $_load_time = '';
    private $_temp_dir = '/tmp';
    private $_path2casper = '/usr/local/bin/'; //path to CasperJS
    private $_options = [];
    private $_customVars = [];

    /**
     * Class constructor
     *
     * @param string $_path2casper The path to the casper executable
     * @param string $_temp_dir The path to the temp dir
     *
     * @return void
     */
    public function __construct($_path2casper = null, $_temp_dir = null)
    {
        if ($_path2casper) {
            $this->_path2casper = $_path2casper;
        }

        if ($_temp_dir) {
            $this->_temp_dir = $_temp_dir;
        }
    }

    /**
     * Set the UserAgent
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->_userAgent = $userAgent;
    }

    /**
     * Add a step to the CasperJS process
     *
     * @param string $step The code to add to casperJS
     */
    public function addStep($step)
    {
        $this->_steps[] = $step;
    }

    /**
     * Add a step to the CasperJS process
     *
     * @return array The steps array
     */
    public function getSteps()
    {
        return $this->_steps;
    }

    /**
     * CasperJS 'then' function
     *
     * @param   $script
     *
     * @return  boolean
     */
    public function then($script)
    {
        $step = <<<FRAGMENT
casper.then(function () {
    $script
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Sets the CasperJS viewport
     *
     * @param   int $width The Viewport Width
     * @param   int $height The Viewport Height
     *
     * @return  boolean
     */
    public function setViewPort($width, $height)
    {
        $this->_viewPortWidth = $width;
        $this->_viewPortHeight = $height;

        $this->then("this.viewport({$width}, {$height});");

        return true;
    }

    /**
     * Sets the debug variable which enables logging to syslog
     *
     * @param boolea $debug
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    /**
     * Sets the custom variable name and value pair
     *
     * @param string $name The custom variable name
     * @param mixed $value The value, default is false
     * @return void
     */
    public function setCustomVar($name, $value = false)
    {
        $this->_customVars[$name] = $value;
    }

    /**
     * Gets the value of the custom variable
     *
     * @param string $name The name of the custom variable
     * @return mixed
     */
    public function getCustomVar($name)
    {
        return $this->_customVars[$name];
    }

    /**
     * Gets the debug variable
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->_debug;
    }

    /**
     * Set specific options to casperJS command
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    /**
     * Sets the output array
     *
     * @param array $output
     */
    private function _setOutput($output)
    {
        $this->_output = $output;
    }

    /**
     * Gets the output array
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Clears the current casperJS script
     *
     * @return void
     */
    private function _clear()
    {
        $this->_script = '';
        $this->_output = array();
        $this->_requestedUrls = array();
        $this->_currentUrl = '';
    }

    /**
     * Open the specified url
     *
     * @param string $url
     *
     * @return boolean
     */
    public function start($url)
    {
        $this->_clear();

        $step = <<<FRAGMENT
var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    colorizerType: 'Dummy'
});
casper.pageSettings = {
    javascriptEnabled:true,
    loadImages:true,
    loadPlugins:true,
};
casper.userAgent('$this->_userAgent');
casper.start().then(function() {
    this.open('$url', {
        headers: {
            'Accept': 'text/html'
        }
    });
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Open URL after the initial opening
     *
     * @param $url
     *
     * @return $this
     */
    public function thenOpen($url)
    {
        $step = <<<FRAGMENT
casper.thenOpen('$url');
FRAGMENT;
        $this->addStep($step);
        return $this;
    }

    /**
     * Open URL after the initial opening
     *
     * @param string $url
     * @param json $jsonData The data in key:value pair json format
     *
     * @return boolean
     */
    public function thenOpenPost($url, $jsonData)
    {
        $step = <<<FRAGMENT
casper.thenOpen('$url', {
    method: "post",
    data: $jsonData
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Fill the form with the array of data
     * then submit it if submit is true
     *
     * @param string $selector
     * @param array $data
     * @param boolean $submit
     *
     * @return boolean
     */
    public function fillForm($selector, $data = [], $submit = false)
    {
        $jsonData = json_encode($data);
        $jsonSubmit = ($submit) ? 'true' : 'false';

        return $this->then("this.fill('{$selector}', {$jsonData}, {$jsonSubmit});");
    }

    /**
     * Sends native keyboard events
     * to the element matching the provided selector:
     *
     * @param string $selector
     * @param string $keys
     *
     * @return boolean
     */
    public function sendKeys($selector, $keys)
    {
        $jsonData = json_encode($keys);

        $this->then("this.sendKeys('{$selector}', {$jsonData});");

        return $this;
    }

    /**
     * Wait until the text appears on the page
     *
     * @param string $text The text to look for
     * @param string $thenScript The script to run when once the text shows
     * @param string $timeoutScript The script to run when there is a timeout
     * @param integer $timeout The timeout value
     *
     * @return boolean
     */
    public function waitForText($text, $thenScript = '', $timeoutScript = '', $timeout = 5000)
    {
        $step = <<<FRAGMENT
casper.waitForText(
    '$text',
    function () {
        $thenScript
    },
    function () {
        $timeoutScript
    },
    $timeout
);
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Wait until timeout
     *
     * @param integer $timeout The timeout value to wait
     * @param string $thenScript The script to run when once the text shows
     *
     * @return boolean
     */
    public function wait($timeout = 5000, $thenScript = '')
    {
        $step = <<<FRAGMENT
casper.wait(
    $timeout,
    function () {
        $thenScript
    }
);
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Wait until the selector is in the HTML
     *
     * @param string $selector The selector to wait for
     * @param string $thenScript The script to run once the selector is found
     * @param string $onTimeoutScript The script to run after timeout
     * @param integer $timeout The timeout value
     *
     * @return boolean
     */
    public function waitForSelector($selector, $thenScript = '', $onTimeoutScript = '', $timeout = 5000)
    {
        $step = <<<FRAGMENT
casper.waitForSelector(
    '$selector',
    function () {
        $thenScript
    },
    function () {
        $onTimeoutScript
    },
    $timeout
);
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Click on the selector
     *
     * @param string $selector The selector to click on
     *
     * @return boolean
     */
    public function click($selector)
    {
        $step = <<<FRAGMENT
casper.then(function() {
    this.click('$selector');
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Gets the current cookies from the session
     *
     * @param string $cookieFile The file to save the cookies too
     *
     * @return boolean
     */
    public function saveCookies($cookieFile)
    {
        $this->then("var fs = require('fs');
var file = fs.open('{$cookieFile}', {
    mode: 'w'
});
file.write(JSON.stringify(phantom.cookies));
file.close();");

        return $this;
    }

    /**
     * Take a screen shot of area inside the selector
     *
     * @param string $selector The selector
     * @param string $filename The file to save the image too
     * @param array The image options
     *
     * @return boolean
     */
    public function captureSelector($filename, $selector, $options = [])
    {
        if (empty($options)) {
            $options = [
                'top' => 0,
                'left' => 0,
                'width' => $this->_viewPortWidth,
                'height' => $this->_viewPortHeight
            ];
        }
        $jsonOptions = json_encode($options);

        return $this->then("this.captureSelector('{$filename}', '{$selector}', {$jsonOptions});");
    }


    /**
     * take a screenshot of the page
     * area defined by
     * array(top left width height)
     *
     * @param array $area
     * @param string $filename
     *
     * @return \Browser\Casper
     */
    public function capture($filename, $options = [])
    {
        if (empty($options)) {
            $options = [
                'top' => 0,
                'left' => 0,
                'width' => $this->_viewPortWidth,
                'height' => $this->_viewPortHeight
            ];
        }
        $jsonOptions = json_encode($options);

        return $this->then("this.capture('{$filename}', {$jsonOptions});");
    }

    /**
     * Take a screenshot of the whole page area defined by viewport width
     * and rendered height
     *
     * @param string $filename The file to save the image too
     *
     * @return boolean
     */
    public function capturePage($filename)
    {
        $step = <<<FRAGMENT
casper.on('load.finished', function() {
    this.capture('$filename', {
        top: 0,
        left: 0,
        width: $this->_viewPortWidth,
        height: this.evaluate(function() {
        return __utils__.getDocumentHeight();
        }),
    });
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * Wwitch to the child frame number $id
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function switchToChildFrame($id)
    {
        return $this->then("this.page.switchToChildFrame($id);");
    }

    /**
     * Get back to parent frame
     *
     * @return boolean
     */
    public function switchToParentFrame()
    {
        return $this->then("this.page.switchToParentFrame();");
    }

    /**
     * Evaluate the JS code
     *
     * @param string The JS code to evaluate
     *
     * @return boolean
     */
    public function evaluate($function)
    {
        return $this->then("casper.evaluate(function() {
    {$function}
});");
    }

    /**
     * Executes the code inside the frame
     *
     * @param string $frameName The franme name to select
     * @param string $script The script to run inside the fram
     * @return  Casper
     */
    public function withFrame($frameName, $script)
    {
        $step = <<<FRAGMENT
casper.withFrame('$frameName', function () {
    $script
});
FRAGMENT;
        $this->addStep($step);

        return $this;
    }

    /**
     * run the casperJS script and return the stdOut
     * in using the output variable
     *
     * @return array
     */
    public function run()
    {
        $output = array();
        $currentURL = self::TAG_CURRENT_URL;
        $currentTitle = self::TAG_CURRENT_TITLE;
        $currentPageContent = self::TAG_CURRENT_PAGE_CONTENT;
        $currentHTML = self::TAG_CURRENT_HTML;

        $step = <<<FRAGMENT
casper.then(function () {
    this.echo('$currentURL' + this.getCurrentUrl());
    this.echo('$currentTitle' + this.getTitle());
    this.echo('$currentPageContent' + this.getPageContent().replace(new RegExp('\\r?\\n','g'), ''));
    this.echo('$currentHTML' + this.getHTML().replace(new RegExp('\\r?\\n','g'), ''));
});

casper.run();
FRAGMENT;
        $this->addStep($step);

        // Write the CasperJS Script
        $filename = tempnam($this->_temp_dir, 'php-casperjs-');
        file_put_contents($filename, implode("\n", $this->getSteps()));

        // echo "SCRIPT\n";
        // echo "======\n";
        // echo file_get_contents($filename);
        // echo "======\n";

        // Options parsing
        $options = [];
        foreach ($this->_options as $option => $value) {
            $options[] = "--{$option}={$value}";
        }

        exec($this->_path2casper.'casperjs '.$filename.implode(" ", $options), $output);
        if (empty($output)) {
            throw new \Exception('Can not find CasperJS in path: '.$this->_path2casper);
        }

        $this->_setOutput($output);
        $this->_processOutput();

        unlink($filename);

        return $output;
    }

    /**
     * process the output after navigation
     * and fill the differents attributes for
     * later usage
     */
    private function _processOutput()
    {
        foreach ($this->getOutput() as $outputLine) {
            if (strpos($outputLine, self::TAG_CURRENT_URL) !== false) {
                $this->_currentUrl = str_replace(self::TAG_CURRENT_URL, '', $outputLine);
            }

            if (strpos($outputLine, "Navigation requested: url=") !== false) {

                $frag0 = explode('Navigation requested: url=', $outputLine);
                $frag1 = explode(', type=', $frag0[1]);
                $this->_requestedUrls[] = $frag1[0];
            }

            if ($this->isDebug()) {
                syslog(LOG_INFO, '[PHP-CASPERJS] '.$outputLine);
            }
            if (strpos($outputLine, self::TAG_CURRENT_PAGE_CONTENT) !== false) {
                $this->_current_page_content = str_replace(self::TAG_CURRENT_PAGE_CONTENT, '', $outputLine);
            }

            if (strpos($outputLine, self::TAG_CURRENT_HTML) !== false) {
                $this->_current_html = str_replace(self::TAG_CURRENT_HTML, '', $outputLine);
            }

            if (strpos($outputLine, " steps in ") !== false) {
                $frag = explode(' steps in ', $outputLine);
                $this->_load_time = $frag[1];
            }

            $this->parseOutputForValues($outputLine);
        }
    }

    /**
     * Checks the ouput line for values that need to be stored
     *
     * @param string $outputLine
     * @return void
     */
    public function parseOutputForValues($outputLine)
    {
        foreach ($this->_customVars as $customVarName => $customVarValue) {
            if (strpos($outputLine, $customVarName) !== FALSE) {
                $this->_customVars[$customVarName] = str_replace($customVarName, '', $outputLine);
            }
        }
    }

    public function getCurrentUrl()
    {
        return $this->_currentUrl;
    }

    public function getRequestedUrls()
    {
        return $this->_requestedUrls;
    }

    public function getCurrentPageContent()
    {
        return $this->_current_page_content;
    }

    public function getHTML()
    {
        return $this->_current_html;
    }

    public function getLoadTime()
    {
        return $this->_load_time;
    }

}
