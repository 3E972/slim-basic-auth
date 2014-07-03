<?php

/*
 * HTTP Basic Authentication
 *
 * Copyright (c) 2013-2014 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-basic-auth
 *
 */

namespace Slim\Middleware;

/**
 * HTTP Basic Authentication
 *
 * Provides HTTP Basic Authentication on given routes
 *
 * @package    Slim
 * @author     Mika Tuupola <tuupola@appelsiini.net>
 */
class HttpBasicAuth extends \Slim\Middleware {

    public $options;

    public function __construct($options = null) {

        /* Default options. */
        $this->options = array(
            "users" => array(),
            "path" => "/",
            "realm" => "Protected",
            "environment" => "HTTP_AUTHORIZATION"
        );

        if ($options) {
            $this->options = array_merge($this->options, (array)$options);
        }
    }

    public function call() {
        $request = $this->app->request;
        $environment = $this->app->environment;

        /* If path matches what is given on initialization. */
        $regex = "@{$this->options["path"]}(/.*)?$@";
        if (true === !!preg_match($regex, $request->getPath())) {
            /* If using PHP in CGI mode. */
            if (isset($_SERVER[$this->options["environment"]])) {
                if (preg_match("/Basic\s+(.*)$/i", $_SERVER[$this->options["environment"]], $matches)) {
                    list($user, $pass) = explode(":", base64_decode($matches[1]));
                }
            } else {
                $user = $environment["PHP_AUTH_USER"];
                $pass = $environment["PHP_AUTH_PW"];
            }

            /* Check if user and passwords matches. */
            if (isset($this->options["users"][$user]) && $this->options["users"][$user] === $pass) {
                $this->next->call();
            } else {
                $this->app->response->status(401);
                $this->app->response->header("WWW-Authenticate", sprintf('Basic realm="%s"', $this->options["realm"]));
                return;
            }
        } else {
            $this->next->call();
        }
    }
}
