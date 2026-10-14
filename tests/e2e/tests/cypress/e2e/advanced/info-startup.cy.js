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

    describe('in a project that has a project.sh', () => {
        // Projects built by the install command carry a project.sh, the e2e app has none
        before(() => cy.exec('touch ../project.sh'));
        after(() => cy.exec('rm -f ../project.sh'));

        it('points to project.sh to stop the server', () => {
            cy.exec('docker exec application php index.php info:startup').then((result) => {
                expect(result.stdout).to.include('./project.sh stop');
                expect(result.stdout).to.not.include('npm run stop');
            });
        });
    });

    it('exits with code 0', () => {
        cy.exec('docker exec application php index.php info:startup', { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode).to.equal(0);
        });
    });

    // Verifies the round trip:
    //   Startup --pwd  ->  AutomatedSettings::set  ->  z_automated_setting.ini
    //                  ->  Configuration boot      ->  config(...)
    describe('--pwd writes host_working_directory through AutomatedSettings', () => {
        const settingsPath = '../z_config/z_automated_setting.ini';
        let original;

        before(() => {
            // File may not exist yet (e.g. on a clean CI checkout) - read
            // via shell so missing-file isn't a hard failure.
            cy.exec('cat ../z_config/z_automated_setting.ini 2>/dev/null || true', { log: false })
                .then((r) => { original = r.stdout; });
        });

        after(() => {
            if (original) {
                cy.writeFile(settingsPath, original, { log: false });
            } else {
                cy.exec('rm -f ../z_config/z_automated_setting.ini', { log: false });
            }
        });

        it('config("automated_host_working_directory") returns the value passed to --pwd', () => {
            const testPwd = '/_/probe/host/working/dir';

            cy.exec(`docker exec application php index.php info:startup --pwd ${testPwd}`);

            cy.request('/Advanced/automatedHostWorkingDirectory').then((res) => {
                expect(res.body).to.eq(testPwd);
            });
        });
    });

});
