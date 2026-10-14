// install builds a new project from stubs/project into an existing folder and
// finishes with a composer require of the framework. That require needs the
// network, so the runs that reach it get a generous timeout.

describe('install Command', () => {
    const PROJECT = '/tmp/install-test';
    const INSTALL_COMMAND = `docker exec application php index.php install ${PROJECT}`;
    const INSTALL_OPTIONS = { timeout: 180000, failOnNonZeroExit: false };

    beforeEach(() => {
        cy.exec(`docker exec application sh -c "rm -rf ${PROJECT} && mkdir ${PROJECT}"`);
    });

    after(() => {
        cy.exec(`docker exec application rm -rf ${PROJECT}`);
    });

    it('fails for a missing folder', () => {
        cy.exec('docker exec application php index.php install /tmp/install-test-missing', { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode).to.not.equal(0);
            expect(result.stdout).to.include('Folder does not exist: /tmp/install-test-missing');
        });
    });

    it('builds the project from the template', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.stdout).to.include('created: index.php');
            expect(result.stdout).to.include('created: composer.json');
            expect(result.stdout).to.include('created: z_config/z_settings.ini');
            expect(result.stdout).to.include('created: app/Controllers/ExampleController.php');
        });

        // copy() drops the executable bit, the command sets it again
        cy.exec(`docker exec application test -x ${PROJECT}/zubzet`);
        cy.exec(`docker exec application test -x ${PROJECT}/project.sh`);
    });

    it('never overwrites files already in the project', () => {
        cy.exec(`docker exec application sh -c "echo keep > ${PROJECT}/index.php"`);

        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.stdout).to.include('exists:  index.php');
            expect(result.stdout).to.include('created: project.sh');
        });

        cy.exec(`docker exec application cat ${PROJECT}/index.php`).then((result) => {
            expect(result.stdout).to.eq('keep');
        });
    });
});
