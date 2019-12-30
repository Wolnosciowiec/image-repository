<?php declare(strict_types=1);

namespace App\Domain\Authentication\Form;

use App\Domain\Common\SharedEntity\Token;

class TokenDetailsForm
{
    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var string[] Empty means all are allowed
     */
    public $allowedMimeTypes = [];

    /**
     * @var int
     */
    public $maxAllowedFileSize = 0;

    /**
     * @var string[]
     */
    public $allowedIpAddresses = [];

    /**
     * @var string[]
     */
    public $allowedUserAgents = [];

    /**
     * @var string
     */
    public $replicationEncryptionKey;

    /**
     * @var string
     */
    public $replicationEncryptionMethod;

    public function toArray(): array
    {
        return [
            Token::FIELD_TAGS                   => $this->tags,
            Token::FIELD_ALLOWED_MIME_TYPES     => $this->allowedMimeTypes,
            Token::FIELD_MAX_ALLOWED_FILE_SIZE  => $this->maxAllowedFileSize,
            Token::FIELD_ALLOWED_IPS            => $this->allowedIpAddresses,
            Token::FIELD_ALLOWED_UAS            => $this->allowedUserAgents,

            // the key will be after submit encrypted with File Repository master key
            // as the key cannot be look up by any user. The key is a limitation on the token, to replicate with
            // zero-knowledge about the data.
            Token::FIELD_REPLICATION_ENC_KEY    => (string) $this->replicationEncryptionKey,
            Token::FIELD_REPLICATION_ENC_METHOD => (string) $this->replicationEncryptionMethod
        ];
    }
}
