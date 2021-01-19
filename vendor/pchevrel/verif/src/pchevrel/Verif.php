<?php
namespace pchevrel;

class Verif
{
    public $protocol = 'http://';
    public $host = 'localhost/';
    public $prefix = '';
    public $path = '';
    public $content = false;
    public $errors = [];
    public $uri;
    public $report_title;
    public $test_count = 0;

    public function __construct($title)
    {
        $this->report_title = $title;
    }

    /**
     * Set the domain we will query
     *
     * @param  string $host Domain name, /ex: www.foo.bar
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        $this->setURI();

        return $this;
    }

    /**
     * Set the protocol (http, https...) we will use
     *
     * @param  string $protocol Protocol used
     * @return $this
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol . '://';
        $this->setURI();

        return $this;
    }

    /**
     * Set the path that prefix that will be prepended to the path
     *
     * @param  string $prefix Prefix for the path
     * @return $this
     */
    public function setPathPrefix($prefix)
    {
        $this->prefix = $prefix;
        $this->setURI();

        return $this;
    }

    /**
     * Set the path that will be appended to the domain name
     *
     * @param  string $path Path of the query, will be appended to the Host
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        $this->setURI();

        return $this;
    }

    /**
     * Set the full URI
     */
    protected function setURI()
    {
        $this->uri = $this->protocol . $this->host . '/' . $this->prefix . $this->path;
    }

    /**
     * Manually set an error
     */
    public function setError($message)
    {
        $this->errors[] = $message;

        return $this;
    }

    /**
     * Fetch the content at the specified URI and stores that in $content
     *
     * @return $this
     */
    public function fetchContent()
    {
        // Set stream options
        $opts = [
          'http' => ['ignore_errors' => true],
        ];

        // Create the stream context
        $context = stream_context_create($opts);

        // Open the file using the defined context
        $this->content = file_get_contents($this->uri, false, $context);

        return $this;
    }

    /**
     * Get the content stored in cache
     *
     * @return string Remote content that was fetched and cached
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the HTTP response code with a GET call
     *
     * @return int The response code
     */
    public function getHTTPResponseCode()
    {
        $headers = get_headers($this->uri);

        return (int) substr($headers[0], 9, 3);
    }

    /**
     * Return the content currently in cache
     *
     * @return mixed The content as a string or False
     */
    public function getCachedContent()
    {
        return $this->content;
    }

    /**
     * Check if the resource is Json data
     * If False, an error will be added to the error stack
     *
     */
    public function isJSON()
    {
        if ($this->content == false) {
            $this->fetchContent();
        }

        $data   = json_decode($this->content);
        $status = is_string($this->content)
            && (is_object($data) or is_array($data))
            && json_last_error() == JSON_ERROR_NONE;

        if (! $status) {
            $this->errors[] =
                $this->colorizeOutput($this->uri . ' is not valid Json', 'red')
                . "\n";
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check if the content is a numeric value.
     * If False, an error will be added to the error stack.
     * @return $this
     */
    public function isNumeric()
    {
        if (! is_numeric($this->content)) {
            $this->errors[] =
                $this->colorizeOutput($this->content . ' is not numeric', 'red')
                . "\n";
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check that the HTTP response code is the one expected
     * @param  int   $code HTTP code such as 200, 301, 404…
     * @return $this
     */
    public function hasResponseCode($code)
    {
        if ($code != $this->getHTTPResponseCode()) {
            $this->errors[] =
                $this->colorizeOutput('HTTP return code error: ', 'yellow')
                . $this->colorizeOutput($this->uri, 'blue')
                . "\n"
                . $this->colorizeOutput('* Expected: ', 'green') . $code
                . "\n"
                . $this->colorizeOutput('* Received: ', 'red') . $this->getHTTPResponseCode()
                . "\n";
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check if an array has the key provided
     * @param  string $key The key we want to check
     * @return $this
     */
    public function hasKey($key)
    {
        $content = json_decode($this->content, true);
        if (! array_key_exists($key, $content)) {
            $this->errors[] =
                $this->colorizeOutput('Unexpected content from: ', 'yellow')
                . $this->colorizeOutput($this->uri, 'blue')
                . "\n"
                . $this->colorizeOutput("The key {$key} is missing in the array", 'red')
                . "\n";
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check if an array has the keys provided
     * @param  string $key The key we want to check
     * @return $this
     */
    public function hasKeys(array $keys)
    {
        foreach ($keys as $key) {
            $this->hasKey($key);
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check if the remote content fetched is equal to what we expect
     * @param  string $string The content we expect
     * @return $this
     */
    public function isEqualTo($string)
    {
        if ($string != $this->content) {
            $this->errors[] =
                $this->colorizeOutput('Unexpected content: ', 'yellow')
                . $this->colorizeOutput($this->uri, 'blue')
                . "\n"
                . $this->colorizeOutput('* Expected: ', 'green') . $string
                . "\n"
                . $this->colorizeOutput('* Received: ', 'red') . $this->content
                . "\n";
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Check if the remote content fetched contains a string
     *
     * @param  string $string The string we expect
     * @return $this
     */
    public function contains($string)
    {
        if (strpos($this->content, $string) === false) {
            $this->errors[] = $this->colorizeOutput('Missing content: ', 'red') . $string;
        }

        $this->test_count++;

        return $this;
    }

    /**
     * Return completion status
     * Useful for bash scripting and Travis CI integration
     *
     * @return int Failure is 1, Success is 0
     */
    public function returnStatus()
    {
        return empty($this->errors) ? 0 : 1;
    }

    /**
     * Get a report of errors
     *
     * @return int Return 0 if no error, 1 if there are errors (useful for Travis)
     */
    public function report()
    {
        $title = 'Report for: ' . $this->report_title . "\n";
        $delimiter = str_repeat("-", strlen($title)) . "\n";
        print $delimiter . $title . $delimiter;

        if (empty($this->errors)) {
            print $this->colorizeOutput(
                $this->test_count . ' tests processed. All tests processed without errors',
                'green'
                ) . "\n";

            return 0;
        }

        foreach ($this->errors as $error) {
            print $error . "\n";
        }

        $error_count = count($this->errors) > 1
            ? 'There are ' . count($this->errors) . " errors"
            : 'There is one error';

        print $this->colorizeOutput($this->test_count . ' tests processed. ' . $error_count, 'red', true) . "\n";

        return 1;
    }

    /**
     * Return text to display in console with background color
     * (useful for tests)
     *
     * @param string  $text       Message to display
     * @param string  $color      color of the message
     * @param boolean $background White on $color background if true, default to false
     *
     * @return string String with ASCII codes to display colored background.
     */
    public static function colorizeOutput($text, $color, $background = false)
    {
        /*  Color guide:
            https://gist.github.com/BenTheElder/4b959e708c3e0f00f51c
        */
        switch ($color) {
            case 'green':
                $color = $background ? "\033[1;37m\033[42m" : "\033[32m";
                break;

            case 'yellow':
                $color = $background ? "\033[1;37m\033[43m" : "\033[33m";
                break;

            case 'red':
                $color = $background ? "\033[1;37m\033[41m" : "\033[31m";
                break;

            case 'blue':
                $color = $background ? "\033[1;37m\033[44m" : "\033[1;34m";
                break;

            default:
                $color = '';
                break;
        }

        return $color . $text . "\033[0m";
    }
}
