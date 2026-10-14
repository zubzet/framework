// Drives Security\Secrets\Encryption and its AesGcmCipher through EncryptionProbeController,
// via the encryptSecret() / decryptSecret() helpers and the Encryption class.
//
// Coverage target: every reachable line/branch in Encryption and AesGcmCipher.

describe('Security/Secrets/Encryption', () => {

    describe('round trip', () => {
        it('encrypts to a URL-safe value naming its cipher that decrypts to the plaintext', () => {
            cy.request('/EncryptionProbe/roundTrip').then((res) => {
                expect(res.body.encrypted).to.match(/^zenc:aes-256-gcm:[A-Za-z0-9_-]+$/);
                expect(res.body.containsPlaintext).to.eq(false);
                expect(res.body.matches).to.eq(true);
            });
        });

        it('round-trips an empty string', () => {
            cy.request('/EncryptionProbe/roundTripEmpty').then((res) => {
                expect(res.body.matches).to.eq(true);
            });
        });

        it('round-trips every byte value, including null bytes and invalid UTF-8', () => {
            cy.request('/EncryptionProbe/roundTripAllBytes').then((res) => {
                expect(res.body.matches).to.eq(true);
            });
        });

        it('round-trips a JSON-encoded array', () => {
            cy.request('/EncryptionProbe/roundTripJson').then((res) => {
                expect(res.body.matches).to.eq(true);
            });
        });

        it('uses a fresh IV for every encryption', () => {
            cy.request('/EncryptionProbe/randomIv').then((res) => {
                expect(res.body.differs).to.eq(true);
            });
        });
    });

    describe('decryption failures', () => {
        const cases = [
            { name: 'a wrong key',                   url: 'wrongKey',          message: /key is wrong/ },
            { name: 'a modified value',              url: 'tampered',          message: /key is wrong or the value was modified/ },
            { name: 'an unknown format',             url: 'unknownFormat',     message: /not in a known encryption format/ },
            { name: 'an unknown cipher',             url: 'unknownCipher',     message: /unknown cipher 'rot13'/ },
            { name: 'a truncated payload',           url: 'malformed',         message: /malformed/ },
            { name: 'characters outside base64url',  url: 'invalidCharacters', message: /malformed/ },
            { name: 'an impossible base64 length',   url: 'invalidLength',     message: /malformed/ },
            { name: 'a non-canonical encoding',      url: 'nonCanonicalEncoding', message: /malformed/ },
        ];

        cases.forEach(({ name, url, message }) => {
            it(`throws a DecryptionException for ${name}`, () => {
                cy.request(`/EncryptionProbe/${url}`).then((res) => {
                    expect(res.body.threw).to.eq(true);
                    expect(res.body.type).to.eq('ZubZet\\Framework\\Security\\Secrets\\DecryptionException');
                    expect(res.body.message).to.match(message);
                });
            });
        });
    });

    describe('key setting', () => {
        const cases = [
            { name: 'a short key on encryption',   url: 'shortKey' },
            { name: 'a missing key on encryption', url: 'missingKey' },
            { name: 'a missing key on decryption', url: 'missingKeyOnDecrypt' },
        ];

        cases.forEach(({ name, url }) => {
            it(`throws a RuntimeException for ${name}`, () => {
                cy.request(`/EncryptionProbe/${url}`).then((res) => {
                    expect(res.body.threw).to.eq(true);
                    expect(res.body.type).to.eq('RuntimeException');
                    expect(res.body.message).to.match(/'encryption_key' must be at least 32 bytes/);
                });
            });
        });
    });
});
