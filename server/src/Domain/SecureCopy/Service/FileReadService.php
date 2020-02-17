<?php declare(strict_types=1);

namespace App\Domain\SecureCopy\Service;

use App\Command\Storage\ReadFileCommand;
use App\Domain\SecureCopy\DTO\FileContent\StreamableFileContent;
use App\Domain\SecureCopy\DTO\FileContent\StreamableFileContentWithEncryptionInformation;
use App\Domain\SecureCopy\Security\MirroringContext;
use App\Domain\SecureCopy\ValueObject\EncryptionAlgorithm;
use App\Domain\SecureCopy\ValueObject\EncryptionPassphrase;

/**
 * Reads a file from local File Repository instance
 * Supports encryption for zero-knowledge securecopy
 *
 * The implementation was made using a shell command because in PHP it is very difficult (if not impossible)
 * to correctly encrypt on-the-fly a bigger file.
 *
 * The encryption using openssl shell command makes decryption a lot easier for potential data recovery.
 */
class FileReadService
{
    protected string $consoleBinPath = 'php ./bin/console';

    /**
     * @param string $filename
     * @param resource $output
     *
     * @param MirroringContext $context
     * @return StreamableFileContentWithEncryptionInformation
     */
    public function getEncryptedStream(string $filename, $output, MirroringContext $context): StreamableFileContentWithEncryptionInformation
    {
        $algorithm  = $context->getEncryptionMethod();
        $passphrase = $context->getPassphrase();
        $iv         = $algorithm->generateInitializationVector();

        return new StreamableFileContentWithEncryptionInformation(
            $filename,
            $this->createReadCallback($filename, $algorithm, $passphrase, $iv, $output),
            $iv,
            $passphrase,
            $algorithm
        );
    }

    public function getPlainStream(string $filename, $output)
    {
        $algorithm = new EncryptionAlgorithm('');
        $passphrase = new EncryptionPassphrase('');

        return new StreamableFileContent($filename, $this->createReadCallback($filename, $algorithm, $passphrase, '', $output));
    }

    public function generateShellCryptoCommand(
        EncryptionAlgorithm $algorithm,
        EncryptionPassphrase $password,
        string $iv,
        bool $decrypt
    ): string {

        $ivStr = $iv ? ' -iv "%iv%" ' : ' '; // not all algorithms requires IV
        $template = 'openssl enc %opts% -%algorithm_name% -K "%passphrase%" ' . $ivStr . ' ';

        return str_replace(
            ['%algorithm_name%', '%passphrase%', '%iv%', '%opts%'],
            [$algorithm->getValue(), $password->getAsHex(), $iv, ($decrypt ? '-d' : '')],
            $template
        );
    }

    /**
     * @param string               $filename
     * @param EncryptionAlgorithm  $algorithm
     * @param EncryptionPassphrase $passphrase
     * @param string               $iv
     * @param resource             $output
     *
     * @return callable
     */
    private function createReadCallback(
        string $filename,
        EncryptionAlgorithm $algorithm,
        EncryptionPassphrase $passphrase,
        string $iv,
        $output
    ): callable {

        return function () use ($filename, $algorithm, $passphrase, $iv, $output) {
            $pipeCommand = '';

            if ($algorithm->isEncrypting()) {
                $pipeCommand = ' | ' . $this->generateShellCryptoCommand($algorithm, $passphrase, $iv, false);
            }

            $command = 'bash -c \'set -e; set -o pipefail; ' . $this->consoleBinPath . ' ' . ReadFileCommand::NAME . ' ' . $filename . $pipeCommand . '\'';

            $descriptorSpec = [
                0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
                1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
                2 => ["pipe", "w"]   // stderr is a file to write to
            ];

            $process = proc_open($command, $descriptorSpec, $pipes, __DIR__ . '/../../../../', $this->getEnv());

            if (!is_resource($process)) {
                throw new \Exception('Cannot spawn console process to read the file');
            }

            while (!feof($pipes[1])) {
                $chunk = fread($pipes[1], 1024 * 1024);
                fwrite($output, $chunk);
            }

            $stdErr = fread($pipes[2], 1024 * 10);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = @proc_close($process);

            if ($exitCode !== 0) {
                throw new \Exception('The file read process - console, returned error: ' . $stdErr);
            }
        };
    }

    protected function getEnv(): array
    {
        $env = [];

        foreach ($_SERVER as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $env[$key] = (string) $value;
        }

        return $env;
    }
}
