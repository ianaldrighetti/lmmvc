<?php
namespace LmMvc;

/**
 * Class HeaderWrapper
 *
 * In order to test certain components of LMMVC a class that provides header-sending functionality must be utilized.
 *
 * @package LmMvc
 */
class HeaderWrapper
{
    /**
     * The HTTP status code.
     * @var int
     */
    private $statusCode;

    /**
     * The HTTP status label (OK, Moved Permanently, etc.).
     * @var string
     */
    private $statusLabel;

    /**
     * The HTTP version (1.0, 1.1).
     * @var float
     */
    private $httpVersion;

    /**
     * An array containing the headers to be sent.
     * @var array
     */
    private $headers;

    /**
     * Indicates whether the send method should actually send the headers when invoked.
     * @var bool
     */
    private $enabled;

    /**
     * Indicates whether headers have been sent.
     * @var bool
     */
    private $sent;

    /**
     * Initializes the Header Wrapper.
     */
    public function __construct()
    {
        $this->statusCode = 200;
        $this->statusLabel = 'OK';
        $this->httpVersion = 1.0;
        $this->enabled = true;
        $this->headers = array();
        $this->sent = false;
    }

    /**
     * Attempts to locate a header's name.
     *
     * @param string $headerToFind
     * @return bool|string If not found, false is returned. Otherwise the array key is returned.
     */
    private function findHeaderKey($headerToFind)
    {
        if (count($this->headers) == 0)
        {
            return false;
        }

        foreach ($this->headers as $header => $value)
        {
            if (strtolower($header) == strtolower($headerToFind))
            {
                return $header;
            }
        }

        return false;
    }

    /**
     * Determines whether a header with that name has already been added.
     *
     * @param string $header
     * @return bool
     */
    public function headerExists($header)
    {
        return $this->findHeaderKey($header) !== false;
    }

    /**
     * Adds a header.
     *
     * @param string $header The header name (such as Content-Type, Location, etc.).
     * @param string $value The value of the header.
     */
    public function add($header, $value)
    {
        // If it already exists, we want to overwrite it.
        if ($this->headerExists($header))
        {
            $header = $this->findHeaderKey($header);
        }

        $this->headers[$header] = $value;
    }

    /**
     * Removes the specified header.
     *
     * @param string $header
     * @return bool
     */
    public function remove($header)
    {
        if (!$this->headerExists($header))
        {
            return false;
        }

        // Remove the header.
        unset($this->headers[$this->findHeaderKey($header)]);

        return true;
    }

    /**
     * Returns the header value, if it exists.
     *
     * @param string $header
     * @return bool
     */
    public function getHeader($header)
    {
        if (!$this->headerExists($header))
        {
            return false;
        }

        return $this->headers[$this->findHeaderKey($header)];
    }

    /**
     * Returns all the headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets the HTTP status code, label and version.
     *
     * @param int $statusCode The status code (200, 301, 307, 404, etc.).
     * @param string $statusLabel The status label (OK, Moved Permanently, etc.).
     * @param float $httpVersion The HTTP version to send (1.0, 1.1).
     */
    public function setStatusCode($statusCode, $statusLabel, $httpVersion = 1.1)
    {
        $this->statusCode = (int)$statusCode;
        $this->statusLabel = $statusLabel;
        $this->httpVersion = $httpVersion == 1.1 ? 1.1 : 1.0;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->statusLabel;
    }

    /**
     * @return float
     */
    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    /**
     * Sets whether the send method will actually send out the headers when invoked (this is for testing purposes).
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = !empty($enabled);
    }

    /**
     * Returns whether the send method is enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sends all the headers that have been added.
     */
    public function send()
    {
        // We will set this even if they aren't really sent.
        $this->sent = true;

        // If this isn't enabled, quit.
        if (!$this->getEnabled())
        {
            return;
        }

        // Now we send the HTTP header.
        header('HTTP/'. $this->getHttpVersion(). ' '. $this->getStatusCode(). ' '. $this->getStatusLabel());

        // And all the custom set ones.
        foreach ($this->headers as $header => $value)
        {
            header($header. ': '. $value);
        }
    }
} 