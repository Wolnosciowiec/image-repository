<?php declare(strict_types=1);

namespace App\Domain\Replication\Security;

use App\Domain\Replication\Entity\ReplicationClient;
use App\Domain\Replication\ValueObject\EncryptionAlgorithm;
use App\Domain\Replication\ValueObject\EncryptionPassphrase;

class ReplicationContext
{
    /**
     * @var bool
     */
    private $canStream;

    /**
     * @var ReplicationClient
     */
    private $client;

    public function __construct(bool $canStream, ReplicationClient $client)
    {
        $this->canStream = $canStream;
        $this->client    = $client;
    }

    public function canRunReplication(): bool
    {
        return $this->canStream;
    }

    public function isEncryptionActive(): bool
    {
        return $this->client->algorithm->isEncrypting();
    }

    public function getPassphrase(): EncryptionPassphrase
    {
        return $this->client->passphrase;
    }

    public function getEncryptionMethod(): EncryptionAlgorithm
    {
        return $this->client->algorithm;
    }
}
