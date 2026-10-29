// bin/install builds a new project from stubs/project into an existing folder. It runs the
// install command on its own, without booting the framework, from the framework checkout
// mounted into the application container.

describe('install Command', () => {
    const PROJECT = '/tmp/install-test';
    const INSTALL_COMMAND = `docker exec application php /var/www/framework/bin/install ${PROJECT}`;
    const INSTALL_OPTIONS = { failOnNonZeroExit: false };

    beforeEach(() => {
        cy.exec(`docker exec application sh -c "chmod -R u+w ${PROJECT} 2>/dev/null; rm -rf ${PROJECT} && mkdir ${PROJECT}"`);
    });

    after(() => {
        cy.exec(`docker exec application sh -c "chmod -R u+w ${PROJECT}; rm -rf ${PROJECT}"`);
    });

    it('fails for a missing folder', () => {
        cy.exec('docker exec application php /var/www/framework/bin/install /tmp/install-test-missing', { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode).to.not.equal(0);
            expect(result.stdout).to.include('Folder does not exist: /tmp/install-test-missing');
        });
    });

    it('fails for a folder that is not writable', () => {
        cy.exec(`docker exec application chmod 555 ${PROJECT}`);

        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode).to.not.equal(0);
            expect(result.stdout).to.include(`Folder is not writable: ${PROJECT}`);
        });
    });

    it('builds the project from the template', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
            expect(result.stdout).to.include('created: index.php');
            expect(result.stdout).to.include('created: z_config/z_settings.ini');
            expect(result.stdout).to.include('created: app/Controllers/ExampleController.php');
        });

        // copy() drops the executable bit, the command sets it again
        cy.exec(`docker exec application test -x ${PROJECT}/zubzet`);
        cy.exec(`docker exec application test -x ${PROJECT}/project`);
    });

    it('boots the built project', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
        });

        // The installer gives a project its own vendor, here the suite's vendor stands in for it
        cy.exec(`docker exec application sh -c "cd ${PROJECT} && php index.php info:startup"`, { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
        });
    });

    it('never overwrites files already in the project', () => {
        cy.exec(`docker exec application sh -c "echo keep > ${PROJECT}/index.php"`);

        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
            expect(result.stdout).to.include('exists:  index.php');
            expect(result.stdout).to.include('created: project');
        });

        cy.exec(`docker exec application cat ${PROJECT}/index.php`).then((result) => {
            expect(result.stdout).to.eq('keep');
        });
    });
});
