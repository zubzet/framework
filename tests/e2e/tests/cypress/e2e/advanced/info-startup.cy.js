describe('info:startup Command', () => {

    it('prints the startup info', () => {
        cy.exec('docker exec application php index.php info:startup').then((result) => {
            const output = result.stdout;

            expect(output).to.include('ZubZet');
            expect(output).to.include('Local');
            expect(output).to.include('env');
            expect(output).to.include('php');
            expect(output).to.include('assets');
            expect(output).to.include('npm run stop');
        });
    });

    it('exits with code 0', () => {
        cy.exec('docker exec application php index.php info:startup', { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode).to.equal(0);
        });
    });

});
