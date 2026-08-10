// Inter-module ordering: without a "modules" ini key composer install order
// applies (guestbook before theme), so the guestbook's own index view wins.
// A "modules" key listing the theme first flips the winner. The tests run in
// order and mutate the config; after() restores the untouched file.

describe('Module ordering', () => {
    const CONFIG_PATH = '../z_config/z_settings.ini';

    before(() => {
        cy.saveConfigBackup();
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    it('renders the guestbook default skin without a modules key', () => {
        cy.request('/guestbook').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('data-test="guestbook-skin">default<');
            expect(res.body).to.not.include('theme-banner');
        });
    });

    it('renders the themed view once the modules key lists the theme first', () => {
        cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
            const separator = content.endsWith('\n') ? '' : '\n';
            cy.writeFile(
                CONFIG_PATH,
                content + separator + 'modules = zubzet/example-theme, zubzet/example-guestbook\n'
            );
        });

        cy.request('/guestbook').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('data-test="guestbook-skin">theme<');
            expect(res.body).to.include('data-test="theme-banner"');
            // Same data behind a different skin, and the userspace footer
            // still beats both module copies.
            expect(res.body).to.include('data-test="guestbook-entry"');
            expect(res.body).to.include('app-footer');
        });
    });
});
