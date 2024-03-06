<?php

namespace Efabrica\HttpClient;

final class HttpAdvancedOptions
{
    /**
     * @param string|null $httpVersion The HTTP version to use, defaults to the best supported version, typically 1.1 or 2.0.
     *
     * @param mixed|null $buffer Whether the content of the response should be buffered or not, or a stream resource
     *                           where the response body should be written, or a closure telling if/where
     *                           the response should be buffered based on its headers.
     *
     * @param array|null $resolve A map of host to IP address that SHOULD replace DNS resolution.
     *
     * @param string|null $proxy By default, the proxy-related env vars handled by curl SHOULD be honored.
     *
     * @param string|null $noProxy A comma-separated list of hosts that do not require a proxy to be reached.
     *
     * @param string|null $bindTo The interface or the local socket to bind to.
     *
     * @param bool|null $verifyPeer Set to true to enable peer verification
     */
    public function __construct(
        private readonly string | null $httpVersion = null,
        private readonly mixed $buffer = null,
        private readonly ?array $resolve = null,
        private readonly string | null $proxy = null,
        private readonly string | null $noProxy = null,
        private readonly ?string $bindTo = null,
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

    public function toArray(): array
    {
        return [
            'http_version' => $this->httpVersion,
            'buffer' => $this->buffer,
            'resolve' => $this->resolve,
            'proxy' => $this->proxy,
            'no_proxy' => $this->noProxy,
            'bindto' => $this->bindTo,
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
