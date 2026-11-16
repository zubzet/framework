describe('info:startup Command', () => {

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
