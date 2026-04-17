describe('info:startup Command', () => {

    it('prints the startup info', () => {
        cy.exec('docker exec application php index.php info:startup').then((result) => {
            const output = result.stdout;

            expect(output).to.include('ZubZet');
            expect(output).to.include('Open');
            expect(output).to.include('Environment');
            expect(output).to.include('PHP Runtime');
            expect(output).to.include('Assets');
            expect(output).to.include('npm run stop');
        });
    });

    it('exits with code 0', () => {
        cy.exec('docker exec application php index.php info:startup', { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode).to.equal(0);
        });
    });

});
