<?php declare(strict_types=1);

namespace App\Domain\Common\SharedEntity;

use App\Domain\Authentication\Helper\TokenSecrets;
use App\Domain\Roles;

class Token
{
    public const FIELD_TAGS                      = 'tags';
    public const FIELD_ALLOWED_MIME_TYPES        = 'allowedMimeTypes';
    public const FIELD_MAX_ALLOWED_FILE_SIZE     = 'maxAllowedFileSize';
    public const FIELD_ALLOWED_IPS               = 'allowedIpAddresses';
    public const FIELD_ALLOWED_UAS               = 'allowedUserAgents';
    public const FIELD_SECURE_COPY_ENC_METHOD    = 'secureCopyEncryptionMethod';
    public const FIELD_SECURE_COPY_ENC_KEY       = 'secureCopyEncryptionKey';
    public const FIELD_SECURE_COPY_DIGEST_METHOD = 'secureCopyDigestMethod';
    public const FIELD_SECURE_COPY_DIGEST_ROUNDS = 'secureCopyDigestRounds';
    public const FIELD_SECURE_COPY_DIGEST_SALT   = 'secureCopyDigestSalt';

    public const SECURE_COPY_FIELDS = [
        self::FIELD_SECURE_COPY_ENC_METHOD,
        self::FIELD_SECURE_COPY_ENC_KEY,
        self::FIELD_SECURE_COPY_DIGEST_METHOD,
        self::FIELD_SECURE_COPY_DIGEST_ROUNDS,
        self::FIELD_SECURE_COPY_DIGEST_SALT
    ];

    // Entity properties
    protected ?string $id    = null;
    protected array   $roles = [];

    // internal
    private bool $alreadyGrantedAdminAccess = false;

    public const ANONYMOUS_TOKEN_ID = '00000000-0000-0000-0000-000000000000';

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCensoredId(): ?string
    {
        if ($this->getId()) {
            return TokenSecrets::getStrippedOutToken($this->getId());
        }

        return null;
    }

    public function isSameAs(Token $token): bool
    {
        return $token->getId() === $this->getId();
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function getRoles(): array
    {
        if (!$this->alreadyGrantedAdminAccess && \in_array(Roles::ROLE_ADMINISTRATOR, $this->roles, true)) {
            $this->setRoles(\array_merge($this->roles, Roles::GRANTS_LIST));
            $this->alreadyGrantedAdminAccess = true;
        }

        return $this->roles;
    }

    public function hasRole(string $roleName): bool
    {
        return \in_array($roleName, $this->getRoles(), true);
    }

    /**
     * @return static
     */
    public static function createAnonymousToken()
    {
        $token = new static();
        $token->id    = self::ANONYMOUS_TOKEN_ID;
        $token->roles = [];

        return $token;
    }

    public function canBePersisted(): bool
    {
        return $this->id !== self::ANONYMOUS_TOKEN_ID;
    }
}
