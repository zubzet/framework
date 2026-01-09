describe('Commands', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Execute Command', () => {
        cy.exec('docker exec application php index.php run core index').then((result) => {
            const output = result.stdout;

            expect(output).to.include('Controller Index');
        });
    });

    it('Execute Command with Parameters', () => {
        cy.exec('docker exec application php index.php run core command test und so').then((result) => {
            const output = JSON.parse(result.stdout);
            expect(output).to.be.an('array').that.includes('test', 'und', 'so');
        });
    });

});