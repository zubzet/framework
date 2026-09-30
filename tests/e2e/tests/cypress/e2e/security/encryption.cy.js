// Drives Security\Encryption through EncryptionProbeController, via the
// encryptSecret() / decryptSecret() helpers and the Encryption class.
//
// Coverage target: every reachable line/branch in Encryption.

describe('Security/Encryption', () => {

    describe('round trip', () => {
        it('encrypts to a prefixed value that decrypts to the plaintext', () => {
            cy.request('/EncryptionProbe/roundTrip').then((res) => {
                expect(res.body.prefixed).to.eq(true);
                expect(res.body.containsPlaintext).to.eq(false);
                expect(res.body.matches).to.eq(true);
            });
        });

        it('round-trips an empty string', () => {
            cy.request('/EncryptionProbe/roundTripEmpty').then((res) => {
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
            { name: 'a wrong key',          url: 'wrongKey',      message: /key is wrong/ },
            { name: 'a modified value',     url: 'tampered',      message: /key is wrong or the value was modified/ },
            { name: 'an unknown format',    url: 'unknownFormat', message: /not in a known encryption format/ },
            { name: 'a truncated payload',  url: 'malformed',     message: /malformed/ },
            { name: 'invalid base64',       url: 'invalidBase64', message: /malformed/ },
        ];

        cases.forEach(({ name, url, message }) => {
            it(`throws a DecryptionException for ${name}`, () => {
                cy.request(`/EncryptionProbe/${url}`).then((res) => {
                    expect(res.body.threw).to.eq(true);
                    expect(res.body.type).to.eq('ZubZet\\Framework\\Security\\DecryptionException');
                    expect(res.body.message).to.match(message);
                });
            });
        });
    });

    describe('key setting', () => {
        ['shortKey', 'missingKey'].forEach((url) => {
            it(`rejects the key for ${url}`, () => {
                cy.request(`/EncryptionProbe/${url}`).then((res) => {
                    expect(res.body.threw).to.eq(true);
                    expect(res.body.type).to.eq('RuntimeException');
                    expect(res.body.message).to.match(/'encryption_key' must be at least 32 characters/);
                });
            });
        });
    });
});
