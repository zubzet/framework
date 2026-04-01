describe('Permission System - Group', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    it('should return group (byId)', () => {
        requestJson('/group/byId').then((output) => {
            expect(output).to.deep.equal({
                "id": 300,
                "name": "group_byId"
            });
        });
    });

    it('should return not a group (byId) because its inactive', () => {
        requestJson('/group/byIdInactive').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should return a list of groups (byIds)', () => {
        const expected = [
            {"id": 302, "name": "group_byIds_1_Active"},
            {"id": 303, "name": "group_byIds_2_Active"}
        ];
        requestJson('/group/byIds').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should get a group (byName)', () => {
        requestJson('/group/byName').then((output) => {
            expect(output).to.deep.equal({
                "id": 328,
                "name": "group_byName_Active"
            });
        });
    });

    it('should not get a group (byName) because its inactive', () => {
        requestJson('/group/byNameInactive').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should return a list of groups (byUser)', () => {
        const expected = [
            {"id": 305, "name": "group_byUser_1_Active"},
            {"id": 306, "name": "group_byUser_2_Active"}
        ];

        requestJson('/group/byUser').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should return groups having access to all specified permissions (byAccessToAll)', () => {
        const shouldInclude = [
            {"id": 319, "name": "group_byAccessToAll_1"},
            {"id": 320, "name": "group_byAccessToAll_2"}
        ];

        const shouldNotInclude = [
            {"id": 300, "name": "group_byId"},
            {"id": 305, "name": "group_byUser_1_Active"},
            {"id": 321, "name": "group_byAccessToAll_3"},
            {"id": 322, "name": "group_byAccessToAll_1_Inactive"},
            {"id": 323, "name": "group_byAccessToAll_1_Inactive-z_role_permission"},
        ];

        requestJson('/group/byAccessToAll').then((output) => {
            shouldInclude.forEach(group => {
                expect(output).to.deep.include(group);
            });

            shouldNotInclude.forEach(group => {
                expect(output).to.not.deep.include(group);
            });
        });
    });

    it('should return groups having access to any of the specified permissions (byAccessToAnyOf)', () => {
        const shouldInclude = [
            {"id": 324, "name": "group_byAccessToAnyOf_1"},
            {"id": 325, "name": "group_byAccessToAnyOf_2"}
        ];

        const shouldNotInclude = [
            {"id": 300, "name": "group_byId"},
            {"id": 326, "name": "group_byAccessToAnyOf_1_Inactive"},
            {"id": 327, "name": "group_byAccessToAnyOf_1_Inactive-z_role_permission"},
        ];

        requestJson('/group/byAccessToAnyOf').then((output) => {
            shouldInclude.forEach(group => {
                expect(output).to.deep.include(group);
            });

            shouldNotInclude.forEach(group => {
                expect(output).to.not.deep.include(group);
            });
        });
    });

    it('should be possible to remove a group', () => {
        requestJson('/group/remove').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should not be able to interact with a removed group', () => {
        requestJson('/group/removeInteraction').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should be possible to update a groups name', () => {
        requestJson('/group/update').then((output) => {
            expect(output).to.deep.equal({
                "id": 311,
                "name": "group_update_NewName"
            });
        });
    });

    it('should be possible to refresh a group`s Data', () => {
        requestJson('/group/refresh').then((output) => {
            expect(output).to.deep.equal({
                "id": 312,
                "name": "group_refresh_UpdatedName"
            });
        });
    });

    it('should show the correct permissions of a group', () => {
        requestJson('/group/getPermissions').then((output) => {
            expect(output).to.deep.equal(
                {"id": 313, "name": "group_getPermissions", "permissions": [
                    "group_getPermissions.should.have.1",
                    "group_getPermissions.should.have.2"
                ]}
            );
        });
    });

    it('should show the correct users of a group', () => {
        requestJson('/group/getUsers').then((output) => {
            expect(output).to.deep.equal(
                {"id": 314, "name": "group_getUsers", "users": [
                    {"id": 301, "name": "group_getUsers_1_Active@cypress.test"},
                    {"id": 302, "name": "group_getUsers_2_Active@cypress.test"}
                ]}
            );
        });
    });

    it('should be possible to create a new group', () => {
        requestJson('/group/add').then((output) => {
            expect(output).to.deep.equal({
                "createdGroupDirect": {"id": 330, "name": "group_add_NewGroup"},
                "createdGroupGet": {"id": 330, "name": "group_add_NewGroup"}
            });
        });
    });

    it('should check if a group has permissions to all specified permissions', () => {
        requestJson('/group/hasAccessToAll').then((output) => {
            expect(output).to.deep.equal({
                "hasAccessToAll_15": true,
                "hasAccessToAll_16": false
            });
        });
    });

    it('should check if a group has permissions to any of the specified permissions', () => {
        requestJson('/group/hasAccessToAnyOf').then((output) => {
            expect(output).to.deep.equal({
                "hasAccessToAnyOf_17": true,
                "hasAccessToAnyOf_18": false
            });
        });
    });

    it('should isolate groups from roles: a group ID must not be found via Role, a role ID must not be found via Group', () => {
        requestJson('/group/isolation').then((output) => {
            expect(output).to.deep.equal({
                "groupFoundByRole": false,
                "roleFoundByGroup": false
            });
        });
    });
});
