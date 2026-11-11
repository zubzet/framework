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

    it('boots the built project', () => {
        cy.exec(INSTALL_COMMAND, INSTALL_OPTIONS).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
        });

        // The installer gives a project its own vendor, here the suite's vendor stands in for it
        cy.exec(`docker exec application sh -c "cd ${PROJECT} && php index.php info:startup"`, { failOnNonZeroExit: false }).then((result) => {
            expect(result.exitCode, result.stdout + result.stderr).to.equal(0);
        });
    });
});
