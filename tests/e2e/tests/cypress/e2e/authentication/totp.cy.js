// The framework generates and verifies two factor codes itself, so its output is
// held against values that do not come from it: the test vectors of RFC 6238.
describe('TOTP codes', () => {

    const probe = (path) => cy.request(path).then((res) => JSON.parse(res.body));

    // RFC 6238 appendix B: the ASCII secret "12345678901234567890" in Base32
    const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    // The RFC lists eight digits for SHA1, a six digit code is their last six
    const RFC_VECTORS = [
        { timestamp: 59, code: '287082' },
        { timestamp: 1111111109, code: '081804' },
        { timestamp: 1111111111, code: '050471' },
        { timestamp: 1234567890, code: '005924' },
        { timestamp: 2000000000, code: '279037' },
        { timestamp: 20000000000, code: '353130' },
    ];

    const codeAt = (timestamp, secret = RFC_SECRET) =>
        probe(`/TwoFactorProbe/codeAt/${secret}/${timestamp}`).then((out) => out.code);

    const verifyAt = (timestamp, code) =>
        probe(`/TwoFactorProbe/verifyAt/${RFC_SECRET}/${timestamp}/${code}`).then((out) => out.valid);

    describe('generation', () => {

        RFC_VECTORS.forEach(({ timestamp, code }) => {
            it(`matches the RFC 6238 vector for ${timestamp}`, () => {
                codeAt(timestamp).should('eq', code);
            });
        });

        it('keeps the code for a whole period and changes it with the next', () => {
            codeAt(30).then((first) => {
                codeAt(59).should('eq', first);
                codeAt(60).should('not.eq', first);
            });
        });

        it('reads a secret regardless of case and padding', () => {
            codeAt(59, `${RFC_SECRET.toLowerCase()}====`).should('eq', '287082');
        });
    });

    describe('verification', () => {

        // 1111111109 is the last second of its period, so the neighbouring periods
        // reach from 59 seconds before it to 30 seconds after it
        const NOW = 1111111109;

        it('accepts the code of the current period', () => {
            verifyAt(NOW, '081804').should('eq', true);
        });

        it('accepts the code of the period before and after', () => {
            verifyAt(NOW + 30, '081804').should('eq', true);
            verifyAt(NOW - 59, '081804').should('eq', true);
        });

        it('rejects a code two periods away', () => {
            verifyAt(NOW + 31, '081804').should('eq', false);
            verifyAt(NOW - 60, '081804').should('eq', false);
        });

        it('rejects a wrong code and a code of the wrong length', () => {
            verifyAt(NOW, '081805').should('eq', false);
            verifyAt(NOW, '81804').should('eq', false);
            verifyAt(NOW, '0081804').should('eq', false);
        });
    });

    describe('provisioning uri', () => {

        const uri = (label, issuer) =>
            cy.request({
                url: '/TwoFactorProbe/provisioningUri',
                qs: { secret: RFC_SECRET, label, issuer },
            }).then((res) => JSON.parse(res.body).uri);

        it('carries issuer, label and secret in the form authenticators read', () => {
            uri('user+a@example.com', 'QA Suite & Co').should(
                'eq',
                'otpauth://totp/QA%20Suite%20%26%20Co%3Auser%2Ba%40example.com'
                + `?issuer=QA%20Suite%20%26%20Co&secret=${RFC_SECRET}`
            );
        });

        it('drops a colon from the label and the issuer, which separates the two', () => {
            uri('a:b@example.com', 'QA: Suite').should(
                'eq',
                `otpauth://totp/QA%20Suite%3Aab%40example.com?issuer=QA%20Suite&secret=${RFC_SECRET}`
            );
        });
    });
});
