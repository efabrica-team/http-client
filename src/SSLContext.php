<?php

namespace Efabrica\HttpClient;


final class SSLContext
{
    /**
     * SSLContext constructor.
     *
     * @param bool|null      $verifyPeer                Require verification of SSL certificate used. Defaults to true.
     * @param bool|null      $verifyHost                Require verification of peer name. Defaults to true.
     * @param string|null    $cafile                    Location of Certificate Authority file on local filesystem for peer verification.
     *                                                   Example: '/path/to/cafile.pem'
     * @param string|null    $capath                    Directory containing suitable certificates for peer verification if cafile is not specified.
     *                                                   Example: '/path/to/certificates'
     * @param string|null    $localCert                 Path to local certificate file (PEM encoded) containing certificate and private key.
     *                                                   Example: '/path/to/localCert.pem'
     * @param string|null    $localPk                   Path to local private key file on filesystem in case of separate files for certificate (localCert) and private key.
     *                                                   Example: '/path/to/localKey.pem'
     * @param string|null    $passphrase                Passphrase with which the localCert file was encoded.
     *                                                   Example: 'myPassphrase'
     * @param string|null    $ciphers                   Sets the list of available ciphers. Defaults to DEFAULT.
     *                                                   Example: 'AES128-SHA:DES-CBC3-SHA'
     * @param string|null    $peerFingerprint           Aborts if the remote certificate digest doesn't match the specified hash.
     *                                                   Can be a string indicating the hashing algorithm ("md5" or "sha1"),
     *                                                   or an array with algorithm names as keys and expected digests as values.
     *                                                   Example: 'sha256' or ['md5' => '123456', 'sha1' => 'abcdef']
     * @param bool           $capturePeerCertChain      If true, a peer_certificate_chain context option will be created containing the certificate chain.
     * @param int|null       $cryptoMethod              Sets the crypto method. Available as of PHP 7.2.0. Defaults to null.
     *                                                   Example: OPENSSL_TLS1_2_METHOD
     */

    public function __construct(
        private readonly ?bool $verifyPeer = null,
        private readonly ?bool $verifyHost = null,
        private readonly string | null $cafile = null,
        private readonly string | null $capath = null,
        private readonly string | null $localCert = null,
        private readonly string | null $localPk = null,
        private readonly string | null $passphrase = null,
        private readonly string | null $ciphers = null,
        private readonly string | null $peerFingerprint = null,
        private readonly bool $capturePeerCertChain = false,
        private readonly ?int $cryptoMethod = null,
    ) {
    }

    /**
     * Useful only for development and testing purposes.
     * Avoids HTTPS certificate verification.
     */
    public static function insecure(bool $insecure = true): self
    {
        return new self(verifyPeer: !$insecure, verifyHost: !$insecure);
    }

    public function toArray(): array
    {
        return [
            'verify_peer' => $this->verifyPeer,
            'verify_host' => $this->verifyHost,
            'cafile' => $this->cafile,
            'capath' => $this->capath,
            'local_cert' => $this->localCert,
            'local_pk' => $this->localPk,
            'passphrase' => $this->passphrase,
            'ciphers' => $this->ciphers,
            'peer_fingerprint' => $this->peerFingerprint,
            'capture_peer_cert_chain' => $this->capturePeerCertChain,
            'crypto_method' => $this->cryptoMethod,
        ];
    }
}
