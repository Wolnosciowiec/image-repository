import os
from tempfile import TemporaryDirectory
from rkd.api.inputoutput import IO
from rkd.api.testing import BasicTestingCase
from bahub.encryption import EncryptionService
from bahub.inputoutput import StreamableBuffer
from bahub.model import Encryption


class TestEncryptionService(BasicTestingCase):
    """
    Functional test - requires gpg binary to be present
    """

    def test_keys_creation_and_listing(self):
        """
        Functional test that checks if GPG keys are correctly created
        Basic successful case
        """

        io = IO()
        service = EncryptionService(io)

        with TemporaryDirectory() as tempdir:
            encryption = self._create_test_encryption(tempdir)

            # create key & list all keys
            service.create_keys(encryption)
            keys_list = service.list_keys(encryption)

            # only one key should be created
            self.assertEqual(
                'Mikhail Bakunin (Generated by Bahub (https://github.com/riotkit-org/backup-repository)) ' +
                '<bakunin@riotkit.local>', keys_list[0]['email'])

            self.assertIsNotNone(keys_list[0]['fingerprint'])
            self.assertEqual(tempdir, keys_list[0]['gpg_home'])
            self.assertEqual(1, len(keys_list))

    def test_encryption_and_decryption(self):
        """
        Generates a key, then encrypts and decrypts a file, compares decrypted file with original before encryption
        """

        io = IO()
        service = EncryptionService(io)

        with TemporaryDirectory() as tempdir:
            encryption = self._create_test_encryption(tempdir)

            try:
                plaintext = b'''
                    Common to all Anarchists is the desire to free society of all 
                    political and social coercive institutions which stand in the 
                    way of development of a free humanity.
                '''

                # prepare a test file for encryption
                with open(tempdir + '/plaintext', 'wb') as f:
                    f.write(plaintext)

                # 1. Create keys
                service.create_keys(encryption)

                # 2. Encrypt a file
                r, w = os.pipe()
                os.write(w, plaintext)
                os.close(w)

                plain_buffer_streamable = StreamableBuffer.from_file(tempdir + '/plaintext')

                encrypted_buffer = service.create_encryption_stream(encryption, stdin=plain_buffer_streamable)
                encrypted_text = encrypted_buffer.read(1024 * 1024 * 64)

                # 2.2. Verify contents - must contain GPG header and footer
                with self.subTest('Verify encrypted text header and footer'):
                    self.assertTrue(encrypted_text.startswith(b'-----BEGIN PGP MESSAGE-----'))
                    self.assertTrue(encrypted_text.endswith(b'-----END PGP MESSAGE-----\n'))

                # 3. Decrypt back a file
                with self.subTest('Verify decryption gives same result as original input before encryption'):
                    # we need to store the encrypted text first to open a normal buffer from file
                    with open(tempdir + '/encrypted.gpg', 'wb') as f:
                        f.write(encrypted_text)

                    decryption_buffer = service.create_decryption_stream(
                        encryption,
                        stdin=StreamableBuffer.from_file(tempdir + '/encrypted.gpg')
                    )

                    # 3.1. Compare original text with decrypted text
                    self.assertEqual(
                        plaintext,
                        decryption_buffer.read(1024 * 1024 * 64)
                    )

            finally:
                # clean up
                encrypted_buffer.close()
                plain_buffer_streamable.close()
                os.close(r)
                decryption_buffer.close()

    @staticmethod
    def _create_test_encryption(tempdir: str):
        return Encryption(
            name='Bakunin-Strong',
            passphrase='To revolt is a natural tendency of life. ' +
                       'Even a worm turns against the foot that crushes it.',
            username='Mikhail Bakunin',
            email='bakunin@riotkit.local',
            algorithm='aes256',
            gnupg_home_path=tempdir,
            key_length=1024,
            key_type='RSA'
        )
