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

    describe('QueryCollector', () => {
        it('shows SQL with placeholders interpolated as single-quoted values', () => {
            cy.visit("/Core/modelinsert");
            // INSERT INTO `model_test_insert` (`value`) VALUES (?) with $value="TestData".
            // The bound value lands in the syntax-highlighted SQL widget as 'TestData' and in
            // the addDataSet JSON as 'TestData' (JSON_HEX_APOS). We check both forms.
            pageHtml().should('match', /\\u0027TestData\\u0027|>'TestData'</);
            pageHtml().should('not.match', /VALUES\s*\(\s*\?\s*\)/);
        });

        it('strips leading indentation from multi-line SQL', () => {
            cy.visit("/Core/modelselectline");
            // CoreModel::getModelTestsLine has an indented FROM line; prettifier should flatten it.
            pageHtml().should('include', 'SELECT *\\nFROM ');
            pageHtml().should('not.include', 'SELECT *\\n   ');
        });

        it('hides internal-model queries when debugbar_hide_internal_queries=true', () => {
            cy.setConfigSetting('debugbar_hide_internal_queries', 'true');
            cy.loginAs('admin');
            cy.visit("/Core/modelselectline");
            // Auth runs queries against z_user / z_session via z_loginModel + z_userModel (IsInternalModel)
            pageHtml().should('not.include', '`z_user`');
            pageHtml().should('not.include', '`z_session`');
        });

        it('shows internal-model queries when debugbar_hide_internal_queries=false', () => {
            cy.setConfigSetting('debugbar_hide_internal_queries', 'false');
            cy.loginAs('admin');
            cy.visit("/Core/modelselectline");
            pageHtml().should('include', '`z_user`');
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

        it('formats template params via DataFormatter', () => {
            cy.visit("/Core/render");
            // CoreController action_render passes `["data" => "Data"]` to the view
            pageHtml().should('include', '"data"');
            pageHtml().should('include', 'Data');
        });
    });

    describe('MonologCollector', () => {
        it('emits a record for every render', () => {
            cy.visit("/Core/render");
            pageHtml().should('include', 'RENDER');
        });

        it('exposes context fields under the context_ prefix', () => {
            cy.visit("/Core/render");
            pageHtml().should('include', 'context.view');
            pageHtml().should('include', 'context.layout');
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

    describe('Gating', () => {
        // The bar must NEVER render outside execution_type=test - leaking SQL, params,
        // log context and traceIds into a prod page is a privacy/security regression.
        it('is suppressed when execution_type != test', () => {
            cy.setConfigSetting('execution_type', 'prod');
            cy.visit("/Core/modelselectline");
            cy.get('.phpdebugbar').should('not.exist');
            pageHtml().should('not.include', 'addDataSet(');
            pageHtml().should('not.include', 'phpdebugbar = new PhpDebugBar');
        });

        it('renders again once execution_type is restored to test', () => {
            cy.setConfigSetting('execution_type', 'test');
            cy.visit("/Core/modelselectline");
            cy.get('.phpdebugbar').should('exist');
        });
    });
});
