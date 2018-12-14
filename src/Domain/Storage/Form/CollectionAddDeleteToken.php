<?php declare(strict_types=1);

namespace App\Domain\Storage\Form;

use App\Domain\Backup\Entity\Authentication\Token;
use App\Domain\Backup\Entity\BackupCollection;

class CollectionAddDeleteToken
{
    /**
     * @var BackupCollection
     */
    public $collection;

    /**
     * @var Token
     */
    public $token;
}
