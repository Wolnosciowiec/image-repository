<?php declare(strict_types=1);

namespace App\Domain\Storage\Provider;

use App\Domain\Storage\Exception\FileRetrievalError;
use App\Domain\Storage\ValueObject\Stream;

class UserUploadProvider
{
    /**
     * @var array
     */
    private array $requestHeaders;

    public function __construct()
    {
        $this->requestHeaders = $_SERVER;
    }

    /**
     * Read user input and create a stream from it
     *
     * @return Stream
     *
     * @throws FileRetrievalError
     */
    public function getStreamFromHttp(): Stream
    {
        if ($this->hasPostedViaPHPUploadMechanism()) {
            $filesIndexes = \array_keys($_FILES);
            $file = $_FILES[$filesIndexes[0]];

            if ($file['error'] === 1) {
                throw FileRetrievalError::fromUploadMaxFileSizeReachedCause();
            }

            return new Stream(fopen($file['tmp_name'], 'rb'));
        }

        if ($this->hasPostedRaw()) {
            return new Stream(fopen('php://input', 'rb'));
        }

        if ($this->isMultipart() && !$this->hasPostedViaPHPUploadMechanism()) {
            throw FileRetrievalError::fromPostMaxSizeReachedCause();
        }

        throw FileRetrievalError::fromEmptyRequestCause();
    }

    private function hasPostedViaPHPUploadMechanism(): bool
    {
        // could check $this->hasUserSentUrlEncodedContentType(), but will not, we can be more fault-tolerant
        return \count($_FILES) > 0;
    }

    private function hasPostedRaw(): bool
    {
        if ($this->hasUserSentUrlEncodedContentType() || $this->isMultipart()) {
            return false;
        }

        $input = fopen('php://input', 'rb');
        $part = fread($input, 512);
        fclose($input);

        return \strlen($part) > 8;
    }

    private function isMultipart(): bool
    {
        $contentType = strtolower($this->requestHeaders['CONTENT_TYPE'] ?? '');

        return strpos($contentType, 'multipart/form-data') !== false;
    }

    private function hasUserSentUrlEncodedContentType(): bool
    {
        $headerValue = ($this->requestHeaders['CONTENT_TYPE'] ?? '');

        return \strtolower(\trim($headerValue)) === 'application/x-www-form-urlencoded';
    }
}
