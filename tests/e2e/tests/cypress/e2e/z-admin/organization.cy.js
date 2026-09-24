describe('Z-Admin - Organization', () => {
    before(() => {
        cy.dbSeed();
    });

    function post(url, body = {}) {
        return cy.request({ method: 'POST', url: url, form: true, body: body })
            .then((res) => JSON.parse(res.body));
    }

    function statusOf(url, body) {
        return cy.request({ method: body ? 'POST' : 'GET', url: url, form: true, body: body, failOnStatusCode: false })
            .its('status');
    }

    // A fading modal swallows keystrokes and clicks while it animates
    function openModal(name) {
        cy.query(`organization-${name}-modal`).invoke('removeClass', 'fade');
        cy.query(`btn-open-organization-${name}`).click();
        cy.query(`organization-${name}-modal`).should('be.visible');
    }

    describe('Navigation', () => {
        it('links the page for users with z.organization.invite', () => {
            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');

            cy.query('btn-organization').should('have.attr', 'href').and('match', /\/z\/organization$/);
        });

        it('hides the link from users without z.organization.invite', () => {
            cy.loginAs('zorg_member');
            cy.visit('/z/organization');

            cy.query('btn-organization').should('not.exist');
        });

        it('shows the dashboard card to admins', () => {
            cy.loginAs('admin');
            cy.visit('/z');

            cy.query('dash-organization').should('have.attr', 'href').and('match', /\/z\/organization$/);
        });
    });

    describe('Organization page', () => {
        it('tells a user without an organization', () => {
            cy.loginAs('zorg_orgless');
            cy.visit('/z/organization');

            cy.contains('You are not a member of any organization.');
            cy.get('#z-organization-invite-form').should('not.exist');
        });

        it('shows no card to a member without permissions', () => {
            cy.loginAs('zorg_member');
            cy.visit('/z/organization');

            cy.get('#z-organization-rename-form').should('not.exist');
            cy.get('#z-organization-invite-form').should('not.exist');
            cy.get('.z-organization-members').should('not.exist');
            cy.get('.z-organization-invites').should('not.exist');
        });

        it('shows only the cards a user may use', () => {
            cy.loginAs('zorg_renamer');
            cy.visit('/z/organization');

            cy.get('#z-organization-rename-form').should('exist');
            cy.get('#z-organization-invite-form').should('not.exist');
            cy.get('.z-organization-members').should('not.exist');
            cy.get('.z-organization-invites').should('not.exist');
        });

        it('shows every card with every permission', () => {
            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');

            cy.get('#z-organization-rename-form').should('exist');
            cy.get('#z-organization-invite-form').should('exist');
            cy.get('.z-organization-members').should('exist');
            cy.get('.z-organization-invites').should('exist');
        });
    });

    describe('Rename', () => {
        beforeEach(() => cy.loginAs('zorg_renamer'));

        it('prefills and saves the name', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/z/organization/rename').as('rename');

            openModal('rename');
            cy.form('name').should('have.value', 'zorg_Rename').clear().type('zorg_Renamed');
            cy.get('#z-organization-rename-form .btn-primary').click();
            cy.wait('@rename');
            cy.get('.z-organization-name').should('have.text', 'zorg_Renamed');

            cy.visit('/z/organization');
            cy.form('name').should('have.value', 'zorg_Renamed');
        });

        it('rejects an empty or too short name', () => {
            post('/z/organization/rename', { isFormData: 1, formAction: 'z-organization-rename', name: '' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'name', type: 'required' });
            });

            post('/z/organization/rename', { isFormData: 1, formAction: 'z-organization-rename', name: 'ab' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'name', type: 'length', info: [3, 255] });
            });
        });

        it('keeps a name with markup from closing the script tag', () => {
            post('/z/organization/rename', {
                isFormData: 1,
                formAction: 'z-organization-rename',
                name: '</script><script>alert(1)</script>',
            }).its('result').should('eq', 'success');

            cy.request('/z/organization').its('body')
                .should('include', '\\u003C\\/script\\u003E')
                .and('not.include', '<script>alert(1)</script>');
        });

        it('refuses a user without z.organization.rename', () => {
            cy.loginAs('zorg_member');

            statusOf('/z/organization/rename', {
                isFormData: 1,
                formAction: 'z-organization-rename',
                name: 'zorg_Hijacked',
            }).should('eq', 403);
        });
    });

    describe('Invite', () => {
        beforeEach(() => cy.loginAs('zorg_manager'));

        const invite = (email) => post('/z/organization/invite', {
            isFormData: 1,
            formAction: 'z-organization-invite',
            email: email,
        });

        it('creates an invitation link through the form', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/z/organization/invite').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_invitable@cypress.test');
            cy.get('#z-organization-invite-form .btn-primary').click();
            cy.wait('@invite');

            cy.query('organization-invite-link').invoke('val').should('match', /\/z\/organization\/invitation\/[0-9a-f]{32}$/);
            cy.form('email').should('have.value', '');
        });

        it('explains in words why an address cannot be invited', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/z/organization/invite').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_nobody@cypress.test');
            cy.get('#z-organization-invite-form .btn-primary').click();
            cy.wait('@invite');

            cy.contains('This user cannot be invited right now.').should('be.visible');
        });

        it('validates the address', () => {
            invite('').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'required' });
            });

            invite('not-an-email').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'filter' });
            });
        });

        it('only invites active accounts outside any organization', () => {
            [
                'zorg_nobody@cypress.test',
                'zorg_member@cypress.test',
                'zorg_other_member@cypress.test',
                'zorg_deactivated@cypress.test',
            ].forEach((email) => {
                invite(email).then((out) => {
                    expect(out.formErrors).to.deep.include({ name: 'email', type: 'user_unavailable' });
                });
            });
        });

        it('allows one open invitation per address', () => {
            invite('zorg_already_invited@cypress.test').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'already_invited' });
            });

            invite('ZORG_ALREADY_INVITED@cypress.test').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'already_invited' });
            });

            invite('zorg_reinvite@cypress.test').its('result').should('eq', 'success');
        });

        it('needs an organization to invite into', () => {
            cy.loginAs('zorg_orgless');

            invite('zorg_invitable@cypress.test').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'user_unavailable' });
            });
        });

        it('refuses a user without z.organization.invite', () => {
            cy.loginAs('zorg_member');

            statusOf('/z/organization/invite', {
                isFormData: 1,
                formAction: 'z-organization-invite',
                email: 'zorg_invitable@cypress.test',
            }).should('eq', 403);
        });
    });

    describe('Open invitations', () => {
        beforeEach(() => cy.loginAs('zorg_manager'));

        it('lists the open invitations of the own organization', () => {
            cy.visit('/z/organization');

            cy.query('organization-invite-806').should('contain', 'zorg_revoke@cypress.test').and('contain', 'valid until');
            cy.query('organization-invite-809').should('contain', 'zorg_already_invited@cypress.test');

            cy.query('organization-invite-802').should('not.exist');
            cy.query('organization-invite-804').should('not.exist');
            cy.query('organization-invite-807').should('not.exist');
            cy.query('organization-invite-808').should('not.exist');
        });

        it('revokes an invitation through its button', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/z/organization/revoke/806').as('revoke');

            cy.query('organization-invite-806').find('[data-test="btn-revoke-organization-invite"]').click();
            cy.wait('@revoke');

            cy.query('organization-invite-806').should('not.exist');
        });

        it('does not revoke an invitation of another organization', () => {
            post('/z/organization/revoke/807').should('deep.include', { result: 'error', message: 'invalid_invite' });
        });

        it('refuses a user without z.organization.invite', () => {
            cy.loginAs('zorg_member');
            statusOf('/z/organization/revoke/809', {}).should('eq', 403);
        });
    });

    describe('Members', () => {
        beforeEach(() => cy.loginAs('zorg_manager'));

        const saveRoles = (userId, roles = {}) => post(`/z/organization/roles/${userId}`, {
            isFormData: 1,
            formAction: `z-organization-member-${userId}`,
            ...roles,
        });

        it('lists the members with their assignable roles', () => {
            cy.visit('/z/organization');

            cy.query('organization-member-800').should('contain', 'zorg_manager@cypress.test');
            cy.query('organization-member-801').should('contain', 'zorg_member@cypress.test');
            cy.get('#z-organization-member-801-form [data-value="261"]').should('exist');
            cy.get('#z-organization-member-801-form [data-value="262"]').should('not.exist');
        });

        it('assigns a role through the form', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/z/organization/roles/801').as('roles');

            cy.query('organization-member-801').find('[data-test="btn-edit-organization-member-roles"]').click();
            cy.get('#z-organization-member-801-form select[name=roles]').select('zorg_Assignable_Role');
            cy.get('#z-organization-member-801-form .btn-primary').click();
            cy.wait('@roles');

            cy.visit('/z/organization');
            cy.get('#z-organization-member-801-form [data-value="260"]').should('exist');
            cy.get('#z-organization-member-801-form [data-value="261"]').should('exist');
        });

        it('replaces only the assignable roles', () => {
            saveRoles(801).its('result').should('eq', 'success');

            cy.loginAs('admin');
            cy.request('/z/edit_user/801').its('body')
                .should('include', '"role":"262"')
                .and('not.include', '"role":"260"')
                .and('not.include', '"role":"261"');
        });

        it('rejects roles not released to organizations', () => {
            saveRoles(801, { 'roles[]': '262' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'roles', type: 'in' });
            });
        });

        it('only changes members of the own organization', () => {
            saveRoles(806, { 'roles[]': '260' }).should('deep.include', { result: 'error', message: 'invalid_member' });
            saveRoles(999999, { 'roles[]': '260' }).should('deep.include', { result: 'error', message: 'invalid_member' });
        });

        it('refuses a user without z.organization.roles', () => {
            cy.loginAs('zorg_member');

            statusOf('/z/organization/roles/801', {
                isFormData: 1,
                formAction: 'z-organization-member-801',
            }).should('eq', 403);
        });
    });

    describe('Invitation', () => {
        it('asks an anonymous visitor to log in', () => {
            cy.request('/z/organization/invitation/zorg_token_open').its('body').should('include', '<title>Login');
        });

        it('answers unknown, foreign and unusable invitations with a 404', () => {
            cy.loginAs('zorg_stranger');

            [
                '',
                'unknown',
                'zorg_token_accept_request',
                'zorg_token_expired',
                'zorg_token_removed_org',
                'zorg_token_revoked',
            ].forEach((token) => {
                statusOf(`/z/organization/invitation/${token}`).should('eq', 404);
            });
        });

        it('answers with a 404 when the user already belongs to an organization', () => {
            cy.loginAs('zorg_member');
            statusOf('/z/organization/invitation/zorg_token_already_member').should('eq', 404);
        });

        it('shows the own invitation outside the admin layout', () => {
            cy.loginAs('zorg_stranger');
            cy.visit('/z/organization/invitation/zorg_token_open');

            cy.query('invitation-organization').should('contain', 'zorg_Main');
            cy.query('invitation-email').should('contain', 'zorg_stranger@cypress.test');
            cy.get('#navbar').should('not.exist');
        });
    });

    describe('Accept', () => {
        const accept = (token) => post(`/z/organization/invitation/${token}/accept`);

        it('joins the organization through the button', () => {
            cy.loginAs('zorg_invitee');
            cy.visit('/z/organization/invitation/zorg_token_accept_ui');
            cy.intercept('POST', '/z/organization/invitation/zorg_token_accept_ui/accept').as('accept');

            cy.query('btn-accept-organization-invitation').click();
            cy.wait('@accept');

            cy.location('pathname').should('eq', '/');
            statusOf('/z/organization/invitation/zorg_token_accept_ui').should('eq', 404);
        });

        it('adds the organization and its group', () => {
            cy.loginAs('zorg_invitee_request');
            accept('zorg_token_accept_request').its('result').should('eq', 'success');

            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');
            cy.query('organization-member-804').should('contain', 'zorg_invitee_request@cypress.test');

            cy.loginAs('admin');
            cy.request('/z/edit_user/804').its('body').should('include', '"role":"263"');
        });

        it('accepts an invitation only once', () => {
            cy.loginAs('zorg_invitee_request');
            accept('zorg_token_accept_request').should('deep.include', { result: 'error', message: 'invalid_token' });
        });

        it('refuses unknown invitations and those of someone else', () => {
            cy.loginAs('zorg_stranger');

            accept('unknown').should('deep.include', { result: 'error', message: 'invalid_token' });
            accept('zorg_token_already_invited').should('deep.include', { result: 'error', message: 'invalid_token' });
        });

        it('refuses a user who already belongs to an organization', () => {
            cy.loginAs('zorg_member');
            accept('zorg_token_already_member').should('deep.include', { result: 'error', message: 'already_in_organization' });
        });

        it('explains a refused accept in words', () => {
            cy.loginAs('zorg_stranger');
            cy.visit('/z/organization/invitation/zorg_token_open');
            cy.intercept('POST', '/z/organization/invitation/zorg_token_open/accept', JSON.stringify({
                result: 'error',
                message: 'already_in_organization',
            }));

            cy.query('btn-accept-organization-invitation').click();

            cy.query('organization-accept-error').should('be.visible').and('have.text', 'You are already a member of an organization.');
        });
    });
});
