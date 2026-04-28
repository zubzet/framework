describe('Permission System - Organization', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    it('should return organization (byId)', () => {
        requestJson('/organization/byId').then((output) => {
            expect(output).to.deep.equal({
                "id": 500,
                "name": "org_byId_Active"
            });
        });
    });

    it('should not return organization (byId) because its inactive', () => {
        requestJson('/organization/byIdInactive').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should return a list of organizations sharing a name (byName)', () => {
        const expected = [
            {"id": 502, "name": "org_byName_Shared"},
            {"id": 503, "name": "org_byName_Shared"}
        ];

        requestJson('/organization/byName').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should not return inactive organizations (byName)', () => {
        requestJson('/organization/byNameInactive').then((output) => {
            expect(output).to.deep.equal([]);
        });
    });

    it('should return the organization of a user (byUser)', () => {
        requestJson('/organization/byUser').then((output) => {
            expect(output).to.deep.equal({
                "id": 505,
                "name": "org_byUser"
            });
        });
    });

    it('should be possible to create a new organization (add)', () => {
        requestJson('/organization/add').then((output) => {
            expect(output).to.deep.equal({
                "createdOrganizationDirect": {"id": 512, "name": "org_add_NewOrganization"},
                "createdOrganizationGet": {"id": 512, "name": "org_add_NewOrganization"}
            });
        });
    });

    it('should be possible to update an organization`s name (updateName)', () => {
        requestJson('/organization/updateName').then((output) => {
            expect(output).to.deep.equal({
                "id": 507,
                "name": "org_updateName_NewName"
            });
        });
    });

    it('should be possible to remove an organization', () => {
        requestJson('/organization/remove').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should show the correct active users of an organization (getUsers)', () => {
        const expectedUsers = [
            {"id": 501, "email": "org_getUsers_1_Active@cypress.test"},
            {"id": 502, "email": "org_getUsers_2_Active@cypress.test"}
        ];

        requestJson('/organization/getUsers').then((output) => {
            expect(output.id).to.equal(506);
            expect(output.name).to.equal("org_getUsers");
            expect(output.users).to.have.deep.members(expectedUsers);
            expect(output.users).to.have.length(expectedUsers.length);
        });
    });

    it('should return active users by organization (User::byOrganization)', () => {
        const expectedUsers = [
            {"id": 501, "email": "org_getUsers_1_Active@cypress.test"},
            {"id": 502, "email": "org_getUsers_2_Active@cypress.test"}
        ];

        requestJson('/organization/userByOrganization').then((output) => {
            expect(output).to.have.deep.members(expectedUsers);
            expect(output).to.have.length(expectedUsers.length);
        });
    });

    it('should return an empty array for an organization with no users (User::byOrganization)', () => {
        requestJson('/organization/userByOrganizationEmpty').then((output) => {
            expect(output).to.deep.equal([]);
        });
    });

    it('should return the organization a user belongs to (User::organization)', () => {
        requestJson('/organization/userOrganization').then((output) => {
            expect(output).to.deep.equal({
                "id": 505,
                "name": "org_byUser"
            });
        });
    });

    it('should return null when a user has no organization', () => {
        requestJson('/organization/userOrganizationNull').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should be possible to assign an organization to a user without one (User::updateOrganization)', () => {
        requestJson('/organization/userUpdateOrganizationAssign').then((output) => {
            expect(output).to.deep.equal({
                "id": 509,
                "name": "org_userOrg_Initial"
            });
        });
    });

    it('should be possible to change a user`s organization (User::updateOrganization)', () => {
        requestJson('/organization/userUpdateOrganizationChange').then((output) => {
            expect(output).to.deep.equal({
                "id": 510,
                "name": "org_userOrg_Updated"
            });
        });
    });

    it('should be possible to unset a user`s organization (User::updateOrganization with null)', () => {
        requestJson('/organization/userUpdateOrganizationUnset').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });
});
