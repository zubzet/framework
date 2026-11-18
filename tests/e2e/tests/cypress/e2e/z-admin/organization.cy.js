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

    function clearMailbox() {
        cy.request({ method: 'DELETE', url: 'http://localhost:3300/api/v1/messages', failOnStatusCode: false });
    }

    function latestMail() {
        return cy.request('http://localhost:3300/api/messages').then((res) => {
            const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
            return body.results[0];
        });
    }

    // A fading modal swallows keystrokes and clicks while it animates
    function openModal(name) {
        cy.query(`organization-${name}-modal`).invoke('removeClass', 'fade');
        cy.query(`btn-open-organization-${name}`).click();
        cy.query(`organization-${name}-modal`).should('be.visible');
    }

    describe('Navigation', () => {
        it('links the page for users with z.organization.manage', () => {
            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');

            cy.query('btn-organization').should('have.attr', 'href').and('match', /\/z\/organization$/);
        });

        it('hides the link from users without z.organization.manage', () => {
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

            // Without a maximum only the members are counted
            cy.query('organization-members-count').should('not.contain', '/');
        });
    });

    describe('Rename', () => {
        beforeEach(() => cy.loginAs('zorg_renamer'));

        it('prefills and saves the name', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/rename').as('rename');

            openModal('rename');
            cy.form('name').should('have.value', 'zorg_Rename').clear().type('zorg_Renamed');
            cy.get('#z-organization-rename-form .btn-primary').click();
            cy.wait('@rename');
            cy.get('.z-organization-name').should('have.text', 'zorg_Renamed');

            cy.visit('/z/organization');
            cy.form('name').should('have.value', 'zorg_Renamed');
        });

        it('rejects an empty or too short name', () => {
            post('/_zubzet/organization/rename', { isFormData: 1, formAction: 'z-organization-rename', name: '' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'name', type: 'required' });
            });

            post('/_zubzet/organization/rename', { isFormData: 1, formAction: 'z-organization-rename', name: 'ab' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'name', type: 'length', info: [3, 255] });
            });
        });

        it('rejects a name of only spaces', () => {
            post('/_zubzet/organization/rename', { isFormData: 1, formAction: 'z-organization-rename', name: '     ' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'name', type: 'required' });
            });
        });

        it('trims the name', () => {
            post('/_zubzet/organization/rename', {
                isFormData: 1,
                formAction: 'z-organization-rename',
                name: '  zorg_Trimmed  ',
            }).its('result').should('eq', 'success');

            cy.visit('/z/organization');
            cy.form('name').should('have.value', 'zorg_Trimmed');
        });

        it('keeps a name with markup from closing the script tag', () => {
            post('/_zubzet/organization/rename', {
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

            statusOf('/_zubzet/organization/rename', {
                isFormData: 1,
                formAction: 'z-organization-rename',
                name: 'zorg_Hijacked',
            }).should('eq', 403);
        });
    });

    describe('Invite', () => {
        beforeEach(() => cy.loginAs('zorg_manager'));

        const invite = (email, fields = {}) => post('/_zubzet/organization/invites', {
            isFormData: 1,
            formAction: 'z-organization-invite',
            email: email,
            ...fields,
        });

        it('creates an invitation link through the form', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/invites').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_invitable@cypress.test');
            cy.form('roleId').select('zorg_Assignable_Role');
            cy.get('#z-organization-invite-form .btn-primary').click();
            cy.wait('@invite');

            // The invitation link ends with the 32 character token
            cy.query('organization-invite-link').invoke('val').should('match', /\/z\/organization\/invitation\/[0-9a-f]{32}$/);
            cy.query('organization-invite-sent').should('not.be.visible');
            cy.form('email').should('have.value', '');
        });

        it('sends the invitation by email without showing the link', () => {
            clearMailbox();
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/invites').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_mailed@cypress.test');
            cy.form('send_email').check();
            cy.get('#z-organization-invite-form .btn-primary').click();
            cy.wait('@invite');

            cy.query('organization-invite-sent').should('be.visible');
            cy.query('organization-invite-result').should('not.be.visible');

            latestMail().then((mail) => {
                expect(mail.to[0]).to.eq('zorg_mailed@cypress.test');
                expect(mail.subject).to.eq('Invitation to zorg_Main');

                cy.request(`http://localhost:3300/api/messages/${mail.id}/html`).then((res) => {
                    // The mailed link carries the 32 character token
                    expect(res.body).to.match(/\/z\/organization\/invitation\/[0-9a-f]{32}/);
                });
            });
        });

        it('explains in words why an address cannot be invited', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/invites').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_member@cypress.test');
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

        it('only invites addresses without an account', () => {
            [
                'zorg_member@cypress.test',
                'zorg_other_member@cypress.test',
                'zorg_deactivated@cypress.test',
                'zorg_account_exists@cypress.test',
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

        it('only hands out roles released to organizations', () => {
            invite('zorg_role_plain@cypress.test', { roleId: '262' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'roleId', type: 'in' });
            });

            invite('zorg_role_unknown@cypress.test', { roleId: '999999' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'roleId', type: 'in' });
            });

            invite('zorg_role_released@cypress.test', { roleId: '260' }).its('result').should('eq', 'success');
            invite('zorg_role_none@cypress.test', { roleId: '' }).its('result').should('eq', 'success');
        });

        it('returns the link only when no email is sent', () => {
            // The invitation link ends with the 32 character token
            invite('zorg_link_only@cypress.test', { send_email: '0' }).its('invite_link')
                .should('match', /\/z\/organization\/invitation\/[0-9a-f]{32}$/);

            invite('zorg_link_mailed@cypress.test', { send_email: '1' }).then((out) => {
                expect(out.result).to.eq('success');
                expect(out).to.not.have.property('invite_link');
            });
        });

        it('only mails with z.organization.invite.email', () => {
            cy.loginAs('zorg_inviter');
            cy.visit('/z/organization');

            cy.get('#z-organization-invite-form [name="send_email"]').should('not.exist');

            // The invitation link ends with the 32 character token
            invite('zorg_inviter_mailed@cypress.test', { send_email: '1' }).its('invite_link')
                .should('match', /\/z\/organization\/invitation\/[0-9a-f]{32}$/);
        });

        it('needs an organization to invite into', () => {
            cy.loginAs('zorg_orgless');

            invite('zorg_invitable@cypress.test').should('deep.include', { result: 'error', message: 'invalid_organization' });
        });

        it('refuses a user without z.organization.invite', () => {
            cy.loginAs('zorg_member');

            statusOf('/_zubzet/organization/invites', {
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
            cy.intercept('POST', '/_zubzet/organization/invites/806/revoke').as('revoke');

            cy.query('organization-invite-806').find('[data-test="btn-revoke-organization-invite"]').click();
            cy.wait('@revoke');

            cy.query('organization-invite-806').should('not.exist');
        });

        it('does not revoke an invitation of another organization', () => {
            post('/_zubzet/organization/invites/807/revoke').should('deep.include', { result: 'error', message: 'invalid_invite' });
        });

        it('refuses a user without z.organization.invite', () => {
            cy.loginAs('zorg_member');
            statusOf('/_zubzet/organization/invites/809/revoke', {}).should('eq', 403);
        });
    });

    describe('Members', () => {
        beforeEach(() => cy.loginAs('zorg_manager'));

        const saveRoles = (userId, roles = {}) => post(`/_zubzet/organization/members/${userId}/roles`, {
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
            cy.intercept('POST', '/_zubzet/organization/members/801/roles').as('roles');

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

            statusOf('/_zubzet/organization/members/801/roles', {
                isFormData: 1,
                formAction: 'z-organization-member-801',
            }).should('eq', 403);
        });
    });

    describe('Capacity', () => {
        const invite = (email) => post('/_zubzet/organization/invites', {
            isFormData: 1,
            formAction: 'z-organization-invite',
            email: email,
        });

        const accept = (token) => post(`/_zubzet/organization/invitations/${token}/accept`, {
            isFormData: 1,
            formAction: 'z-organization-accept',
            password: 'zorg_Password1',
            password_repeat: 'zorg_Password1',
        });

        const saveOrganization = (id, name, maxCapacity) => post(`/z/organizations/${id}`, {
            isFormData: 1,
            formAction: 'form',
            name: name,
            max_capacity: maxCapacity,
        });

        it('refuses an invitation while members and open invitations fill the seats', () => {
            cy.loginAs('zorg_capacity_manager');

            invite('zorg_over_capacity@cypress.test').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'organization_full' });
            });
        });

        it('shows the members next to the maximum number of users', () => {
            cy.loginAs('zorg_capacity_manager');
            cy.visit('/z/organization');

            // Matches the line breaks and indentation Blade leaves around the numbers
            cy.query('organization-members-count').invoke('text').invoke('replace', /\s+/g, ' ').invoke('trim').should('eq', '3 / 4');
        });

        it('explains in words that the organization is full', () => {
            cy.loginAs('zorg_capacity_manager');
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/invites').as('invite');

            openModal('invite');
            cy.form('email').type('zorg_over_capacity@cypress.test');
            cy.get('#z-organization-invite-form .btn-primary').click();
            cy.wait('@invite');

            cy.contains('This organization has reached its maximum number of users.').should('be.visible');
        });

        it('frees a seat with a revoked invitation and does not count deleted members', () => {
            cy.loginAs('zorg_capacity_manager');

            post('/_zubzet/organization/invites/814/revoke').its('result').should('eq', 'success');
            invite('zorg_seat_a@cypress.test').its('result').should('eq', 'success');

            invite('zorg_seat_b@cypress.test').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'email', type: 'organization_full' });
            });
        });

        it('frees a seat by deleting a member', () => {
            cy.loginAs('zorg_capacity_manager');

            post('/_zubzet/organization/members/822/delete').its('result').should('eq', 'success');
            invite('zorg_seat_c@cypress.test').its('result').should('eq', 'success');
        });

        it('has no limit without a maximum', () => {
            cy.loginAs('zorg_manager');

            invite('zorg_unlimited@cypress.test').its('result').should('eq', 'success');
        });

        it('refuses to accept an invitation while the members fill the seats', () => {
            accept('zorg_token_accept_full').then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'password', type: 'organization_full' });
            });
        });

        it('does not count the invitation being accepted', () => {
            accept('zorg_token_accept_self').its('result').should('eq', 'success');
        });

        it('lets the admin raise the capacity', () => {
            cy.loginAs('admin');

            saveOrganization(805, 'zorg_AcceptFull', '2').its('result').should('eq', 'success');
            accept('zorg_token_accept_full').its('result').should('eq', 'success');
        });

        it('lets the admin set and clear the capacity', () => {
            cy.loginAs('admin');

            saveOrganization(807, 'zorg_AdminCapacity', '5').its('result').should('eq', 'success');
            cy.visit('/z/organizations/807');
            cy.form('max_capacity').should('have.value', '5');

            saveOrganization(807, 'zorg_AdminCapacity', '').its('result').should('eq', 'success');
            cy.visit('/z/organizations/807');
            cy.form('max_capacity').should('have.value', '');
        });

        it('sets the capacity when the admin creates an organization', () => {
            cy.loginAs('admin');

            post('/z/add_organization', { isFormData: 1, formAction: 'form', name: 'zorg_AdminCreated', max_capacity: '7' })
                .its('result').should('eq', 'success');

            cy.request('/z/organizations').its('body').then((list) => {
                // The id in front of the name, e.g. [10002]</code></small> zorg_AdminCreated
                const id = list.match(/\[(\d+)\]<\/code>\s*<\/small>\s*zorg_AdminCreated/)[1];
                cy.request(`/z/organizations/${id}`).its('body').should('include', 'value: 7,');
            });
        });

        it('validates the capacity', () => {
            cy.loginAs('admin');

            ['abc', '0', '-3'].forEach((value) => {
                post('/z/add_organization', { isFormData: 1, formAction: 'form', name: 'zorg_Invalid', max_capacity: value }).then((out) => {
                    expect(out.formErrors).to.deep.include({ name: 'max_capacity', type: 'range', info: [1, 1000000] });
                });
            });
        });

        it('does not limit the admin assigning a user', () => {
            cy.loginAs('admin');

            post('/z/edit_user/826', {
                isFormData: 1,
                formAction: 'form',
                email: 'zorg_unassigned@cypress.test',
                organization: '804',
            }).its('result').should('eq', 'success');

            cy.request('/z/edit_user/826').its('body').should('include', 'value: 804,');
        });
    });

    describe('Deleting and removing members', () => {
        beforeEach(() => cy.loginAs('zorg_members_manager'));

        const deleteMember = (id) => post(`/_zubzet/organization/members/${id}/delete`);
        const removeMember = (id) => post(`/_zubzet/organization/members/${id}/remove`);

        it('lists deleted members marked and only removable', () => {
            cy.visit('/z/organization');

            cy.query('organization-member-833').should('contain', 'zorg_members_deleted@cypress.test');
            cy.query('organization-member-833').find('[data-test="organization-member-deleted"]').should('contain', 'Deleted');
            cy.query('organization-member-833').find('[data-test="btn-remove-organization-member"]').should('exist');
            cy.query('organization-member-833').find('[data-test="btn-delete-organization-member"]').should('not.exist');
            cy.query('organization-member-833').find('[data-test="btn-edit-organization-member-roles"]').should('not.exist');
            cy.get('#z-organization-member-833-form').should('not.exist');

            cy.query('organization-member-831').find('[data-test="organization-member-deleted"]').should('not.exist');
            cy.query('organization-member-831').find('[data-test="btn-remove-organization-member"]').should('exist');
            cy.query('organization-member-831').find('[data-test="btn-delete-organization-member"]').should('exist');
        });

        it('keeps the member when the confirmation is declined', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/members/835/delete').as('delete');
            cy.on('window:confirm', () => false);

            cy.query('organization-member-835').find('[data-test="btn-delete-organization-member"]').click();

            cy.get('@delete.all').should('have.length', 0);
            cy.query('organization-member-835').find('[data-test="organization-member-deleted"]').should('not.exist');
        });

        it('deletes a member through its button', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/members/831/delete').as('delete');
            cy.on('window:confirm', () => true);

            cy.query('organization-member-831').find('[data-test="btn-delete-organization-member"]').click();
            cy.wait('@delete');

            // The member stays listed, marked as deleted
            cy.query('organization-member-831').find('[data-test="organization-member-deleted"]').should('exist');
            cy.query('organization-member-831').find('[data-test="btn-delete-organization-member"]').should('not.exist');
        });

        it('stops the session of a deleted member', () => {
            cy.loginAs('zorg_deletable');
            cy.visit('/z/organization');

            cy.query('username').should('exist');
            cy.get('#z-organization-rename-form').should('not.exist');
        });

        it('removes a member through its button', () => {
            cy.visit('/z/organization');
            cy.intercept('POST', '/_zubzet/organization/members/832/remove').as('remove');
            cy.on('window:confirm', () => true);

            cy.query('organization-member-832').find('[data-test="btn-remove-organization-member"]').click();
            cy.wait('@remove');

            cy.query('organization-member-832').should('not.exist');

            // The account stays without organization, group and assignable role, but keeps its plain role
            cy.loginAs('admin');
            cy.request('/z/edit_user/832').its('body')
                .should('match', /name: "organization",[\s\S]*?value: "",/)
                .and('include', '"role":"262"')
                .and('not.include', '"role":"260"')
                .and('not.include', '"role":"264"');
        });

        it('only deletes and removes members of the own organization', () => {
            deleteMember(806).should('deep.include', { result: 'error', message: 'invalid_member' });
            removeMember(806).should('deep.include', { result: 'error', message: 'invalid_member' });
            deleteMember(999999).should('deep.include', { result: 'error', message: 'invalid_member' });
            removeMember(999999).should('deep.include', { result: 'error', message: 'invalid_member' });
        });

        it('does not delete a deleted member again', () => {
            deleteMember(833).should('deep.include', { result: 'error', message: 'invalid_member' });
            deleteMember(831).should('deep.include', { result: 'error', message: 'invalid_member' });
        });

        it('does not remove a member twice', () => {
            removeMember(832).should('deep.include', { result: 'error', message: 'invalid_member' });
        });

        it('removes a deleted member from the list', () => {
            removeMember(833).its('result').should('eq', 'success');
            removeMember(833).should('deep.include', { result: 'error', message: 'invalid_member' });

            cy.visit('/z/organization');
            cy.query('organization-member-833').should('not.exist');
        });

        it('deletes a member through the endpoint', () => {
            deleteMember(835).its('result').should('eq', 'success');

            cy.visit('/z/organization');
            cy.query('organization-member-835').find('[data-test="organization-member-deleted"]').should('exist');
        });

        it('shows the buttons only with their permissions', () => {
            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');

            cy.query('organization-member-801').should('exist');
            cy.query('btn-remove-organization-member').should('not.exist');
            cy.query('btn-delete-organization-member').should('not.exist');
        });

        it('refuses a user without the permission', () => {
            cy.loginAs('zorg_member');
            statusOf('/_zubzet/organization/members/800/delete', {}).should('eq', 403);
            statusOf('/_zubzet/organization/members/800/remove', {}).should('eq', 403);

            // Holds the other permissions of the organization, but not these
            cy.loginAs('zorg_manager');
            statusOf('/_zubzet/organization/members/801/delete', {}).should('eq', 403);
            statusOf('/_zubzet/organization/members/801/remove', {}).should('eq', 403);

            // May delete, but not remove
            cy.loginAs('zorg_capacity_manager');
            statusOf('/_zubzet/organization/members/821/remove', {}).should('eq', 403);
        });
    });

    describe('Invitation', () => {
        it('shows the invitation to a visitor without login', () => {
            cy.visit('/z/organization/invitation/zorg_token_open');

            cy.query('invitation-organization').should('contain', 'zorg_Main');
            cy.query('invitation-email').should('contain', 'zorg_open@cypress.test');
            cy.query('organization-accept-form').should('be.visible');
            cy.get('#navbar').should('not.exist');
        });

        it('answers unknown and unusable invitations with a 404', () => {
            [
                '',
                'unknown',
                'zorg_token_expired',
                'zorg_token_removed_org',
                'zorg_token_revoked',
                'zorg_token_account_exists',
            ].forEach((token) => {
                statusOf(`/z/organization/invitation/${token}`).should('eq', 404);
            });
        });

        it('shows a logged in visitor the same page', () => {
            cy.loginAs('zorg_stranger');
            cy.visit('/z/organization/invitation/zorg_token_open');

            cy.query('invitation-organization').should('contain', 'zorg_Main');
            cy.query('invitation-email').should('contain', 'zorg_open@cypress.test');
        });
    });

    describe('Accept', () => {
        const accept = (token, fields = { password: 'zorg_Password1', password_repeat: 'zorg_Password1' }) => post(
            `/_zubzet/organization/invitations/${token}/accept`,
            { isFormData: 1, formAction: 'z-organization-accept', ...fields },
        );

        // The admin page of an account, found through the id the user list shows in front of its address
        const accountOf = (email) => {
            cy.loginAs('admin');

            return cy.request('/z/edit_user').its('body').then((list) => {
                // The id in front of the address, e.g. [814]</code></small> zorg_x@cypress.test
                const id = list.match(new RegExp(`\\[(\\d+)\\]</code>\\s*</small>\\s*${email}`))[1];
                return cy.request(`/z/edit_user/${id}`).its('body');
            });
        };

        it('creates the account through the form and leads to the login', () => {
            cy.visit('/z/organization/invitation/zorg_token_accept_ui');
            cy.intercept('POST', '/_zubzet/organization/invitations/zorg_token_accept_ui/accept').as('accept');

            cy.form('password').type('zorg_Password1');
            cy.form('password_repeat').type('zorg_Password1');
            cy.get('#z-organization-accept-form .btn-primary').click();
            cy.wait('@accept');

            // The login page, with or without a trailing slash
            cy.location('pathname').should('match', /\/login\/?$/);
            cy.getCookie('z_login_token').should('not.exist');
            statusOf('/z/organization/invitation/zorg_token_accept_ui').should('eq', 404);

            cy.intercept('POST', '/login').as('login');
            cy.query('username').type('zorg_invitee@cypress.test');
            cy.query('password').type('zorg_Password1');
            cy.query('btn-login').click();
            cy.wait('@login');
            cy.getCookie('z_login_token').should('exist');
        });

        it('validates the password', () => {
            accept('zorg_token_validate', { password: '', password_repeat: '' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'password', type: 'required' });
                expect(out.formErrors).to.deep.include({ name: 'password_repeat', type: 'required' });
            });

            accept('zorg_token_validate', { password: 'ab', password_repeat: 'ab' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'password', type: 'length', info: [3, 1024] });
            });

            accept('zorg_token_validate', { password: 'zorg_Password1', password_repeat: 'zorg_Password2' }).then((out) => {
                expect(out.formErrors).to.deep.include({ name: 'password_repeat', type: 'password_mismatch' });
            });
        });

        it('adds the organization and its group, once', () => {
            accept('zorg_token_accept_request').its('result').should('eq', 'success');
            accept('zorg_token_accept_request').its('formErrors').should('deep.include', { name: 'password', type: 'invalid_token' });

            cy.loginAs('zorg_manager');
            cy.visit('/z/organization');
            cy.get('.z-organization-members').should('contain', 'zorg_invitee_request@cypress.test');

            accountOf('zorg_invitee_request@cypress.test').should('include', '"role":"263"');
        });

        it('adds the role of the invitation', () => {
            accept('zorg_token_accept_role').its('result').should('eq', 'success');

            accountOf('zorg_invitee_role@cypress.test').should('include', '"role":"260"').and('include', '"role":"263"');
        });

        it('leaves out a role that is no longer released', () => {
            accept('zorg_token_unreleased_role').its('result').should('eq', 'success');

            accountOf('zorg_unreleased@cypress.test').should('include', '"role":"263"').and('not.include', '"role":"262"');
        });

        it('refuses unknown, expired and revoked invitations', () => {
            ['unknown', 'zorg_token_expired', 'zorg_token_removed_org', 'zorg_token_revoked'].forEach((token) => {
                accept(token).its('formErrors').should('deep.include', { name: 'password', type: 'invalid_token' });
            });
        });

        it('refuses an address that has an account by now', () => {
            accept('zorg_token_account_exists').its('formErrors').should('deep.include', { name: 'password', type: 'invalid_token' });
        });
    });
});
