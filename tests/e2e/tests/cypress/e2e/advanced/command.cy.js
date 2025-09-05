describe('Commands', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Execute Command', () => {
        cy.exec('docker exec application php8 index.php run core index').then((result) => {
            const output = result.stdout;

            expect(output).to.include('Controller Index');
        });
    });

    it('Execute Command with Parameters', () => {
        cy.exec('docker exec application php8 index.php run core parameters test und so').then((result) => {
            const output = result.stdout;

            expect(output).to.include('Array\n(\n    [0] => test\n    [1] => und\n    [2] => so\n)');
        });
    });

});