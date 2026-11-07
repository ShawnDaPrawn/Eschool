<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use stdClass;
use Throwable;

/**
 * The W3C Data Integrity proof with the eddsa-jcs-2022 cryptosuite (Data
 * Integrity EdDSA Cryptosuites v1.0): the document and the proof options are
 * each canonicalised with JCS and hashed with SHA-256, and the Ed25519
 * signature over both hashes is the multibase proofValue.
 */
class DataIntegrityProof
{
    public const TYPE = 'DataIntegrityProof';

    public const CRYPTOSUITE = 'eddsa-jcs-2022';

    /**
     * @param  array<string, mixed>  $document  the unsigned credential
     * @return array<string, mixed> the credential with its proof
     */
    public function sign(array $document, string $secretKey, string $verificationMethod, string $created): array
    {
        $options = ['type' => self::TYPE, 'cryptosuite' => self::CRYPTOSUITE, 'created' => $created,
            'verificationMethod' => $verificationMethod, 'proofPurpose' => 'assertionMethod'];
        $signature = sodium_crypto_sign_detached($this->hashData($document, $options), $secretKey);

        return $document + ['proof' => $options + ['proofValue' => Multibase::encode($signature)]];
    }

    /**
     * Whether the credential's proof is a valid eddsa-jcs-2022 signature by one
     * of the trusted keys. The document is the decoded JSON (objects as stdClass).
     *
     * @param  array<string, string>  $trusted  verification method => Ed25519 public key
     */
    public function verify(stdClass $document, array $trusted): bool
    {
        try {
            $proof = $document->proof ?? null;
            if (is_array($proof) && count($proof) === 1) {
                $proof = $proof[0];
            }
            if (! $proof instanceof stdClass || ($proof->type ?? null) !== self::TYPE || ($proof->cryptosuite ?? null) !== self::CRYPTOSUITE
                || ($proof->proofPurpose ?? null) !== 'assertionMethod' || ! is_string($proof->proofValue ?? null) || ! is_string($proof->verificationMethod ?? null)) {
                return false;
            }
            $public = $trusted[$proof->verificationMethod] ?? null;
            if ($public === null) {
                return false;
            }
            $signature = Multibase::decode($proof->proofValue);
            if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
                return false;
            }
            $unsecured = clone $document;
            unset($unsecured->proof);
            $options = clone $proof;
            unset($options->proofValue);
            if (isset($options->{'@context'})) {
                // A proof that carries its own context must match the document's.
                if (Jcs::encode($options->{'@context'}) !== Jcs::encode($document->{'@context'} ?? null)) {
                    return false;
                }
                unset($options->{'@context'});
            }

            return sodium_crypto_sign_verify_detached($signature, $this->hashData($unsecured, $options), $public);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>|stdClass  $document
     * @param  array<string, mixed>|stdClass  $options
     */
    private function hashData(array|stdClass $document, array|stdClass $options): string
    {
        $config = $options instanceof stdClass ? clone $options : (object) $options;
        $context = $document instanceof stdClass ? ($document->{'@context'} ?? null) : ($document['@context'] ?? null);
        if ($context !== null) {
            $config->{'@context'} = $context;
        }

        return hash('sha256', Jcs::encode($config), true).hash('sha256', Jcs::encode($document), true);
    }
}
