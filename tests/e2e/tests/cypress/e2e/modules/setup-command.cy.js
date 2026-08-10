// module:setup merges each module's z_settings.ini defaults into the app
// config, module by module, first module winning shared keys, under a
// per-package comment header. before() strips any previously merged module
// keys so the run starts clean; after() restores the untouched file.

describe('module:setup command', () => {
    const CONFIG_PATH = '../z_config/z_settings.ini';
    const SETUP_COMMAND = 'docker exec application php index.php module:setup';

    let mergedConfig = null;

    before(() => {
        cy.saveConfigBackup();
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            const cleaned = content
                .split('\n')
                .filter((line) => {
                    if (/^(guestbook_title|guestbook_page_size|theme_accent|modules)\s*=/.test(line)) return false;
                    if (line.startsWith('; Defaults added by module:setup')) return false;
                    return true;
                })
                .join('\n');
            cy.writeFile(CONFIG_PATH, cleaned);
        });
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    it('falls back to the code default before any merge', () => {
        cy.request('/ModuleHost/config').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('data-test="guestbook-config">fallback-title<');
        });
    });

    it('merges missing keys module by module, first module winning shared keys', () => {
        cy.exec(SETUP_COMMAND).then(({ stdout }) => {
            expect(stdout).to.include('zubzet/example-guestbook: added guestbook_title');
            expect(stdout).to.include('zubzet/example-guestbook: added guestbook_page_size');
            expect(stdout).to.include('zubzet/example-theme: added theme_accent');
            // The theme also ships guestbook_title, but guestbook already merged it.
            expect(stdout).to.not.include('zubzet/example-theme: added guestbook_title');
        });

        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            expect(content).to.include('; Defaults added by module:setup from zubzet/example-guestbook');
            expect(content).to.include('; Defaults added by module:setup from zubzet/example-theme');

            // Values are written quoted so they round-trip through the ini parser.
            const titleLines = content.match(/^guestbook_title = .*$/gm);
            expect(titleLines).to.have.length(1);
            expect(titleLines[0]).to.eq('guestbook_title = "Signatures Book"');

            mergedConfig = content;
        });

        cy.request('/ModuleHost/config').then((res) => {
            expect(res.body).to.include('data-test="guestbook-config">Signatures Book<');
        });

        cy.request('/guestbook').then((res) => {
            expect(res.body).to.include('data-test="guestbook-title">Signatures Book<');
        });
    });

    it('is idempotent: a rerun merges nothing and leaves the file untouched', () => {
        cy.exec(SETUP_COMMAND).then(({ stdout }) => {
            expect(stdout).to.include('zubzet/example-guestbook: nothing to merge');
            expect(stdout).to.include('zubzet/example-theme: nothing to merge');
        });

        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            expect(content).to.eq(mergedConfig);
        });
    });
});
