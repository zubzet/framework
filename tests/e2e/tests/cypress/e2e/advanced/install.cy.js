// install builds a new project from stubs/project into an existing folder and
// finishes with a composer require of the framework. That require needs the
// network, so the runs that reach it get a generous timeout.

describe('install Command', () => {
    const PROJECT = '/tmp/install-test';
    const INSTALL_COMMAND = `docker exec application php index.php install ${PROJECT}`;
    const INSTALL_OPTIONS = { timeout: 180000, failOnNonZeroExit: false };

    beforeEach(() => {
        cy.exec(`docker exec application sh -c "chmod -R u+w ${PROJECT} 2>/dev/null; rm -rf ${PROJECT} && mkdir ${PROJECT}"`);
    });

    after(() => {
        cy.exec(`docker exec application sh -c "chmod -R u+w ${PROJECT}; rm -rf ${PROJECT}"`);
    });

    it('fails for a missing folder', () => {
        cy.exec('docker exec application php index.php install /tmp/install-test-missing', { failOnNonZeroExit: false }).then((result) => {
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

    it('builds the project from the template and requires the framework', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
            expect(result.stdout).to.include('created: index.php');
            expect(result.stdout).to.include('created: composer.json');
            expect(result.stdout).to.include('created: z_config/z_settings.ini');
            expect(result.stdout).to.include('created: app/Controllers/ExampleController.php');
        });

        // copy() drops the executable bit, the command sets it again
        cy.exec(`docker exec application test -x ${PROJECT}/zubzet`);
        cy.exec(`docker exec application test -x ${PROJECT}/project`);

        // The framework landed in the project's own vendor folder
        cy.exec(`docker exec application test -f ${PROJECT}/vendor/autoload.php`);
    });

    it('boots the built project without a COMPOSER_VENDOR_DIR', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
        });

        // Without the variable the entry script has to find vendor/ on its own
        cy.exec(`docker exec application sh -c "cd ${PROJECT} && env -u COMPOSER_VENDOR_DIR php index.php info:startup"`, { failOnNonZeroExit: false }).then((result) => {
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
