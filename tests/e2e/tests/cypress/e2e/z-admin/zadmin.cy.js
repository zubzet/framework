describe('Z-Admin Panel', () => {
    before(() => {
        cy.dbSeed();
    });

    it('LoginAs', () => {
        cy.visit("/admin/loginas");
        cy.contains("0");

        cy.visit("/admin/loginas");
        cy.contains("1");
    });

    it('Opens Z-Admin', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        // Layout nav buttons (in the sidebar)
        cy.query("btn-database").should("exist");
        cy.query("btn-edit-user").should("exist");
        cy.query("btn-add-user").should("exist");
        cy.query("btn-roles").should("exist");

        // Dashboard cards (in the content area) link into each admin section
        cy.query("dash-database").should("have.attr", "href").and("match", /\/z\/database$/);
        cy.query("dash-maintenance").should("have.attr", "href").and("match", /\/z\/maintenance$/);
        cy.query("dash-edit-user").should("have.attr", "href").and("match", /\/z\/edit_user$/);
        cy.query("dash-add-user").should("have.attr", "href").and("match", /\/z\/add_user$/);
        cy.query("dash-roles").should("have.attr", "href").and("match", /\/z\/roles$/);
        cy.query("dash-groups").should("have.attr", "href").and("match", /\/z\/groups$/);
        cy.query("dash-back").should("have.attr", "href").and("match", /\/$/);
        cy.query("dash-logout").should("have.attr", "href").and("match", /\/login\/logout$/);
    });

    it('Goes into Database', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        // Check we are in Z-Admin and relevant buttons exit
        cy.query("btn-database").should("exist");
        cy.query("btn-database").click();

        // Check that data is imported
        cy.query("table-amount").should("exist");
        cy.query("table-amount").invoke('text').then((text) => {
            const numTables = parseInt(text);
            expect(numTables).to.be.greaterThan(0);
        });

        cy.query("row-amount").should("exist");
        cy.query("row-amount").invoke('text').then((text) => {
            const numTables = parseInt(text);
            expect(numTables).to.be.greaterThan(0);
        });

        cy.query("table-name-admin_panel_test").should("exist");
        cy.query("table-name-admin_panel_test").find("a").first().click();

        // page 1-5 should exist

        let topId;
          // top id should be different
        cy.query("row-id").first().invoke('text').then((text) => {
            topId = text;
        });
        cy.query("pagination-next").should("exist");
        cy.query("pagination-next").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-previous").should("exist");
        cy.query("pagination-previous").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-last").should("exist");
        cy.query("pagination-last").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-first").should("exist");
        cy.query("pagination-first").click();

        cy.query("row-id").first().invoke('text').then((text) => {
            expect(text).not.to.eq(topId);
            topId = text;
        });

        cy.query("pagination-page-1").should("exist");
        cy.query("pagination-page-2").should("exist");
        cy.query("pagination-page-3").should("exist");
        cy.query("pagination-page-4").should("exist");
        cy.query("pagination-page-5").should("exist");

        cy.query("sort-by").click();
    });

    it('Goes into add User', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-add-user").should("exist");
        cy.query("btn-add-user").click();


        cy.form("email").should("exist");
        cy.form("password").should("exist");
        cy.get('.btn.btn-primary').should("exist");

        // ZForm has an in-flight guard (`isSending`) that drops a second
        // click while the previous AJAX is still pending. Under xdebug
        // coverage the round-trip takes 2-3s, so wait for each submit
        // before clicking again. This is due to the hash calculation.
        cy.intercept('POST', '/z/add_user').as('addUser');

        // try to create invalid user
        cy.get('.btn.btn-primary').last().click();
        cy.wait('@addUser', { timeout: 30000 });
        cy.get(".form-text.text-danger").should("exist");

        // create the actual user
        cy.form("email").type("exampleUser@domain.de");
        cy.form("password").type("examplePass");
        cy.get('.btn.btn-primary').last().click();
        cy.wait('@addUser', { timeout: 30000 }).its('response.statusCode').should('eq', 200);

        // check for error
        cy.get(".alert.sticky-top.alert-danger").should("not.exist");
        cy.get(".form-text.text-danger").should('not.be.visible');

    });

    // Validation paths in ZController::action_add_user not covered by the
    // happy-path "Goes into add User" flow above.
    describe('action_add_user (validation)', () => {
        beforeEach(() => cy.loginAs("admin"));

        function postAdd(body) {
            // hasFormData() requires the isFormData flag (set by Z.Forms).
            return cy.request({
                method: 'POST',
                url: '/z/add_user',
                form: true,
                failOnStatusCode: false,
                body: { isFormData: 1, ...body },
            }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
        }

        it('rejects a password under 3 characters', () => {
            // PasswordHash::create throws UnmetInputRequirements for <3 chars,
            // which the action's catch turns into a "password/filter" form error.
            postAdd({ email: 'short_pw@cypress.test', password: 'ab' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'password', type: 'filter' });
            });
        });

        it('rejects an invalid email format', () => {
            postAdd({ email: 'not-an-email', password: 'goodpass' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'filter' });
            });
        });

        it('accepts an empty email (user created without an email)', () => {
            // Empty email is intentionally allowed — the action converts "" to null.
            postAdd({ email: '', password: 'goodpass' }).then((out) => {
                expect(out.result).to.eq('success');
            });
        });
    });

    it('Goes into edit User', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-edit-user").should("exist");
        cy.query("btn-edit-user").click();

        cy.query("user").contains("customer@zierhut-it.de").should("exist");
        cy.query("user").contains("customer@zierhut-it.de").click();

        cy.form("email").should("exist");

    });

    // Validation + mutation paths in ZController::action_edit_user not covered
    // by the form-rendering "Goes into edit User" smoke test above.
    describe('action_edit_user (mutations)', () => {
        beforeEach(() => cy.loginAs("admin"));

        function postEdit(userId, body) {
            // hasFormData() requires the isFormData flag (set by Z.Forms).
            return cy.request({
                method: 'POST',
                url: `/z/edit_user/${userId}`,
                form: true,
                failOnStatusCode: false,
                body: { isFormData: 1, ...body },
            }).then((res) => typeof res.body === 'string' ? JSON.parse(res.body) : res.body);
        }

        it('updates the email field successfully', () => {
            // Use user 5 ("customer-new") so we don't disturb fixtures used elsewhere.
            postEdit(5, { email: 'customer-new-renamed@zierhut-it.de' }).then((out) => {
                expect(out.result).to.eq('success');
            });

            // Re-open the edit page and confirm the new email is loaded.
            cy.visit('/z/edit_user/5');
            cy.form('email').should('have.value', 'customer-new-renamed@zierhut-it.de');
        });

        it('rejects an invalid email format', () => {
            postEdit(5, { email: 'not-an-email' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'filter' });
            });
        });

        it('rejects an email already taken by another user', () => {
            // user 1 is the admin; their email is unique to user 1.
            postEdit(5, { email: 'admin@zierhut-it.de' }).then((out) => {
                expect(out.result).to.eq('formErrors');
                expect(out.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'email' && e.type === 'unique')
                );
            });
        });

        it('adds a role to the user via the roles CED', () => {
            // Add role 100 (seeded "user_byRole") to user 5 via the CED form.
            postEdit(5, {
                email: 'customer-new-renamed@zierhut-it.de',
                roles: [{ Z: 'create', role: '100' }],
            }).then((out) => {
                expect(out.result).to.eq('success');
            });

            // Verify the link exists in z_user_role via the existing user probe.
            cy.request('/user/getRoles').then(() => {
                // Direct DB probe is heavier than needed; reopen the edit page
                // and assert the role select inside the roles CED renders the
                // newly-added role 100.
                cy.visit('/z/edit_user/5');
                cy.get('select[name=role]')
                    .should('exist')
                    .find(`option[value="100"]:selected`)
                    .should('exist');
            });
        });
    });

    it('Goes into roles and creates a role with a single permission', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-roles").should("exist");
        cy.query("btn-roles").click();
        cy.query("role-100").should("exist"); // seeded "user_byRole"
        cy.query("role-create").should("exist");
        cy.query("role-create").click();

        cy.form("name").should("exist");
        cy.form("name").type("test");
        cy.get('.btn.btn-primary').find('i.fas.fa-plus').should('exist');
        cy.wait(500);
        cy.get('.btn.btn-primary').filter(':has(i.fas.fa-plus)').click();
        cy.get('input#input-2').type('test');
        cy.get('.btn.btn-primary').last().click();
        cy.get(".form-text.text-danger").should('not.be.visible');
    });

    it('Shows a validation error when an added permission row is left empty', () => {
        cy.visit("/admin/loginas");
        cy.visit("/z");

        cy.query("btn-roles").click();
        cy.query("role-create").click();

        cy.form("name").type("test");
        cy.get('.btn.btn-primary').find('i.fas.fa-plus').should('exist');
        cy.wait(500);
        cy.get('.btn.btn-primary').filter(':has(i.fas.fa-plus)').click();
        cy.get('.btn.btn-primary').filter(':has(i.fas.fa-plus)').click();
        cy.get('input#input-2').type('test');
        cy.get('.btn.btn-primary').last().click();
        cy.get(".form-text.text-danger").should('be.visible');
    });

    // ZController::action_roles delete branch.
    describe('action_roles (delete)', () => {
        it('deactivates a role and removes it from the active list', () => {
            cy.loginAs("admin");

            // Role 245 ("zadmin_RoleDeleteTarget") is seeded specifically for
            // this test (see app/Database/seed/ZAdmin.sql). Using a dedicated
            // seed entry keeps the test independent and avoids triggering
            // earlier-test-leaves-empty-name-role collisions from createRole().
            const roleId = 245;

            cy.visit('/z/roles');
            cy.query(`role-${roleId}`).should('exist');

            cy.request({
                method: 'POST',
                url: `/z/roles/${roleId}`,
                form: true,
                body: { action: 'delete' },
            }).then((delRes) => {
                const delBody = typeof delRes.body === 'string' ? JSON.parse(delRes.body) : delRes.body;
                expect(delBody.result).to.eq('success');
            });

            cy.visit('/z/roles');
            cy.query(`role-${roleId}`).should('not.exist');
        });

        it('denies delete to users without admin.roles.delete', () => {
            cy.loginAs("customer");
            cy.request({
                method: 'POST',
                url: '/z/roles/100',
                form: true,
                failOnStatusCode: false,
                body: { action: 'delete' },
            }).then((res) => {
                expect(res.status).to.eq(403);
            });
        });
    });


    // ZController::action_login_as
    // The admin panel's permission-checked sudo distinct from the test app's /admin/loginas
    // which is the test apps shortcut that logs in as admin directly without permission checks.
    describe('action_login_as (sudo)', () => {
        it('admin with admin.su can log in as another user; the session records execUserId', () => {
            cy.loginAs("admin");

            // Capture admin's id and the pre-sudo token so we can verify both
            // rotate after sudo and that execUserId points back at the admin.
            let adminUserId;
            let preSudoToken;
            cy.getCookie('z_login_token').then((cookie) => {
                preSudoToken = cookie.value;
            });
            cy.request('/session/whoami').then((res) => {
                const out = JSON.parse(res.body);
                expect(out.isLoggedIn).to.eq(true);
                adminUserId = out.userId;
            });

            cy.request({
                url: '/z/login_as/3', // user 3 = customer
                followRedirect: false,
                failOnStatusCode: false,
            });

            // The cookie rotates to a fresh session token after sudo.
            cy.getCookie('z_login_token').then((cookie) => {
                expect(cookie.value, 'token rotates after sudo').to.not.eq(preSudoToken);
            });

            // The new session is for customer (userId=3); execUserId points
            // at the admin who sudoed in so an audit trail is preserved.
            cy.request('/session/whoami').then((res) => {
                const out = JSON.parse(res.body);
                expect(out.isLoggedIn).to.eq(true);
                expect(out.userId).to.eq(3);
                expect(out.execUserId).to.eq(adminUserId);
            });

            // Logging out of a sudoed session unwinds back to the admin
            // (Response::logout calls loginAs(execUserId) when sudoed). The
            // cookie should rotate to a fresh session token, and whoami
            // should report the original admin again with no exec.
            let postSudoToken;
            cy.getCookie('z_login_token').then((cookie) => {
                postSudoToken = cookie.value;
            });

            cy.request('/login/logout');

            cy.getCookie('z_login_token').then((cookie) => {
                expect(cookie, 'cookie present after logout-unwind').to.exist;
                expect(cookie.value, 'token rotates after logout').to.not.eq(postSudoToken);
                expect(cookie.value, 'token rotates after logout').to.not.eq(preSudoToken);
            });

            cy.request('/session/whoami').then((res) => {
                const out = JSON.parse(res.body);
                expect(out.isLoggedIn).to.eq(true);
                expect(out.userId, 'logout unwinds sudo to admin').to.eq(adminUserId);
                expect(out.execUserId, 'no longer sudoed').to.eq(adminUserId);
            });

            cy.request('/session/whoami').then((res) => {
                const out = JSON.parse(res.body);
                expect(out.isLoggedIn).to.eq(true);
                expect(out.userId, 'logout unwinds sudo to admin').to.eq(adminUserId);
                expect(out.execUserId, 'no longer sudoed').to.eq(adminUserId);
            });
        });

        it('non-admin without admin.su gets denied', () => {
            cy.loginAs("customer");

            cy.request({
                url: '/z/login_as/1',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.eq(403);
            });
        });
    });

    // ZController::action_groups
    // Lights up views/administration/groups.php and z_generalModel::getGroups().
    describe('action_groups (admin)', () => {
        it('renders the groups admin page for admins', () => {
            cy.loginAs("admin");
            cy.visit("/z/groups");

            // The Permission seed inserts ~30 groups (is_group=1) — the
            // template should render the heading plus a few known names.
            cy.contains('Groups');
            cy.query('group-300').should('exist').and('contain', 'group_byId');
        });

        it('a newly created group shows up in the admin list', () => {
            cy.loginAs("admin");

            // Capture the id from the probe response — earlier role-create
            // tests in this spec bump z_role auto_increment, so the id is
            // not deterministic across the suite.
            cy.request('/group/add').then((res) => {
                const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
                const newId = body.createdGroupDirect.id;

                cy.visit("/z/groups");
                cy.query(`group-${newId}`).should('exist').and('contain', 'group_add_NewGroup');
            });
        });

        it('a removed (inactive) group is no longer in the admin list', () => {
            cy.loginAs("admin");

            // Sanity: both seeded "group_remove*" groups show up initially.
            cy.visit("/z/groups");
            cy.query('group-309').should('exist').and('contain', 'group_remove');
            cy.query('group-310').should('exist').and('contain', 'group_removeInteraction');

            // The probe deactivates group 309 ("group_remove").
            cy.request('/group/remove');

            cy.visit("/z/groups");
            cy.query('group-309').should('not.exist');
            // Untouched group 310 still shows — proves the filter is targeted.
            cy.query('group-310').should('exist');
        });

        it('non-admin without admin.groups.list gets denied', () => {
            cy.loginAs("customer");

            cy.request({
                url: '/z/groups',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.eq(403);
            });
        });
    });

    // Tests that assert end-state (no UI flow inside the assertion) — kept
    // at the bottom so they observe the data created by the flows above.
    describe('Post-flow assertions', () => {
        // Verifies the user created by "Goes into add User" landed in the DB.
        it('Checks that user has been created', () => {
            cy.loginAs("admin");
            cy.visit("/z");
            cy.query("btn-edit-user").click();
            cy.query("user").contains("exampleUser@domain.de").should("exist");
        });

        // ZController::action_database (csv branch) → z_adminDashboardModel::exportToCsv
        it('streams a CSV file for the requested table', () => {
            cy.loginAs("admin");

            cy.request('/z/database/admin_panel_test/csv').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.headers['content-type']).to.match(/text\/csv/i);
                expect(res.headers['content-disposition'])
                    .to.match(/attachment/i);

                // Should contain CSV-formatted text — at minimum a header row
                // with comma-separated column names, plus at least one data row.
                expect(res.body).to.match(/^[^\n]+,[^\n]+/);
                expect(res.body.split(/\r?\n/).length).to.be.greaterThan(1);
            });
        });

        it('renders a CSV export button that points at the csv endpoint', () => {
            cy.loginAs("admin");
            cy.visit("/z/database/admin_panel_test");

            cy.query("btn-csv-export")
                .should("exist")
                .and("have.attr", "href")
                .and("match", /\/z\/database\/admin_panel_test\/csv$/);
        });

        it('denies CSV export to users without admin.database', () => {
            cy.loginAs("customer");

            cy.request({
                url: '/z/database/admin_panel_test/csv',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.status).to.eq(403);
            });
        });
    });

});