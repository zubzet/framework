describe('Configuration', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Configuration', () => {
        cy.visit("/Core/Configuration");
        cy.contains("TestValue");
    });

    // A misconfigured showErrors used to stop the bootstrap with a TypeError,
    // followed by a second fatal from the slow-request shutdown handler.
    // Integers like "1" are accepted, anything else falls back to 0.
    describe('showErrors', () => {
        ['abc', '', '7', '1', ' 2'].forEach((value) => {
            it(`boots with showErrors = '${value}'`, () => {
                cy.exec(`docker exec -e CONFIG_SHOWERRORS='${value}' application php index.php info:startup`, {
                    failOnNonZeroExit: false,
                }).then((result) => {
                    expect(result.exitCode).to.eq(0);
                    expect(result.stdout).to.include('PHP Runtime');
                    expect(result.stdout).to.not.include('Uncaught');
                });
            });
        });
    });
});
