<?php

namespace synacksa\casperjsphp

/**
 * CasperJS wrapper
 *
 * @author Garth Michel <syndrome@gmail.com>
 *
 */
class Casper extends \Browser\Casper
{

    /**
     * Gets the current cookies from the session
     *
     * @return \Browser\Casper
     * @author  Garth Michel
     */
    public function saveCookies($cookieFile)
    {
        $fragment = <<<FRAGMENT
casper.then(function () {
var fs = require('fs');
var file = fs.open('$cookieFile', {
    mode: 'w'
});
file.write(JSON.stringify(phantom.cookies));
file.close();
});
FRAGMENT;

        $this->_script .= $fragment;

        return $this;

    }


}
