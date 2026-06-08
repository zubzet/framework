// Guarantees the framework's password API stays compatible with consuming apps
// (e.g. jouri), which call model("z_login")->checkPassword($pw, $hash, $salt) with
// the 3-argument shape (no scheme) on their own user tables. The scheme is
// inferred from the salt, so legacy and native hashes both keep verifying.

describe('Authentication/PasswordHash - consumer API compatibility', () => {

    it('verifies a legacy hash via 3-arg checkPassword (existing users)', () => {
        cy.request('/ApiCompatProbe/legacy3Arg').then((res) => {
            expect(res.body.correct).to.eq(true);
            expect(res.body.wrong).to.eq(false);
        });
    });

    it('verifies a native hash via 3-arg checkPassword (new users)', () => {
        cy.request('/ApiCompatProbe/native3Arg').then((res) => {
            expect(res.body.correct).to.eq(true);
            expect(res.body.wrong).to.eq(false);
        });
    });
});
