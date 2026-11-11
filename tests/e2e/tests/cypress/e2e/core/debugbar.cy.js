describe('DebugBar', () => {
    before(() => {
        cy.dbSeed();
        cy.saveConfigBackup();
    });

    after(() => {
        cy.restoreConfigBackup();
    });

    const pageHtml = () => cy.document().its('documentElement.outerHTML');

    describe('Bootstrap', () => {
        it('renders the bar container on a normal page', () => {
            cy.visit("/Core/modelselectline");
            cy.get('.phpdebugbar').should('exist');
        });

        it('exposes all three custom collectors in the dataset', () => {
            cy.visit("/Core/modelselectline");
            pageHtml().should('include', '"queries"');
            pageHtml().should('include', '"templates"');
            pageHtml().should('include', '"monolog"');
        });
    });

    describe('TemplateCollector', () => {
        it('records the rendered view name and default layout', () => {
            cy.setConfigSetting('debugbar_hide_internal_queries', 'true');
            cy.visit("/Core/render");
            pageHtml().should('include', 'core/render (layout: layout/default_layout.php)');
        });

        // Note: a "custom layout is collected" assertion needs a layout that calls
        // layout_essentials_body() so the DebugBar UI is actually rendered. The e2e app's
        // layout/new_layout intentionally omits it, so we cover the layout-tagging behavior via
        // the default-layout test above.
    });

    describe('MonologCollector', () => {
        it('emits a record for every render', () => {
            cy.visit("/Core/render");
            pageHtml().should('include', 'RENDER');
        });

        it('exposes processor extras (traceId, file) under the extra_ prefix', () => {
            cy.visit("/Core/render");
            pageHtml().should('include', 'extra.traceId');
            pageHtml().should('include', 'extra.file');
        });
    });

    describe('Inherited widgets', () => {
        it('uses the MessagesWidget search input for the monolog tab', () => {
            cy.visit("/Core/render");
            cy.get('.phpdebugbar input[name="search"]').should('exist');
        });
    });

    describe('ResolutionCollector', () => {
        it('labels a userspace controller and model resolution', () => {
            cy.visit("/Core/modelselectline");
            pageHtml().should('include', '"resolutions"');
            pageHtml().should('include', 'controllers: CoreController');
            pageHtml().should('include', 'userspace');
        });

        it('labels a module resolution with the package name', () => {
            cy.visit("/Guestbook/index");
            pageHtml().should('include', 'controllers: GuestbookController');
            pageHtml().should('include', 'module:zubzet\\/example-guestbook');
        });

        it('labels loaded route files with their origin', () => {
            cy.visit("/Core/render");
            pageHtml().should('include', 'routes: DefaultRoutes.php');
            pageHtml().should('include', 'framework');
        });
    });

    describe('Gating', () => {
        it('renders again once execution_type is restored to test', () => {
            cy.setConfigSetting('execution_type', 'test');
            cy.visit("/Core/modelselectline");
            cy.get('.phpdebugbar').should('exist');
        });
    });
});
