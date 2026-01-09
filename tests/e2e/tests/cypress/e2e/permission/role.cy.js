describe('Permission System - User', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    it('should return role (byId)', () => {
        requestJson('/role/byId').then((output) => {
            expect(output).to.deep.equal({
                "id": 200,
                "name": "role_byId"
            });
        });
    });

    it('should return not a role (byId) because its inactive', () => {
        requestJson('/role/byIdInactive').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should return a list of roles (byIds)', () => {
        const expected = [
            {"id": 202, "name": "role_byIds_1_Active"},
            {"id": 203, "name": "role_byIds_2_Active"}
        ];
        requestJson('/role/byIds').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should a role (byName)', () => {
        requestJson('/role/byName').then((output) => {
            expect(output).to.deep.equal({
                "id": 228,
                "name": "role_byName_Active"
            });
        });
    });

    it('should not get a role (byName) because its inactive', () => {
        requestJson('/role/byNameInactive').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should return a list of roles (byUser)', () => {
        const expected = [
            {"id": 205, "name": "role_byUser_1_Active"},
            {"id": 206, "name": "role_byUser_2_Active"}
        ];

        requestJson('/role/byUser').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should return roles having access to all specified permissions (byAccessToAll)', () => {
        const shouldInclude = [
            {"id": 219, "name": "role_byAccessToAll_1"},
            {"id": 220, "name": "role_byAccessToAll_2"}
        ];

        const shouldNotInclude = [
            {"id": 200, "name": "role_byId"},
            {"id": 205, "name": "role_byUser_1_Active"},
            {"id": 221, "name": "role_byAccessToAll_3"},
            {"id": 223, "name": "role_byAccessToAll_1_Inactive"},
            {"id": 224, "name": "role_byAccessToAll_1_Inactive-z_role_permission"},
        ];

        requestJson('/role/byAccessToAll').then((output) => {
            shouldInclude.forEach(role => {
                expect(output).to.deep.include(role);
            });

            shouldNotInclude.forEach(role => {
                expect(output).to.not.deep.include(role);
            });
        });
    });

    it('should return roles having access to any of the specified permissions (byAccessToAnyOf)', () => {
        const shouldInclude = [
            {"id": 224, "name": "role_byAccessToAnyOf_1"},
            {"id": 225, "name": "role_byAccessToAnyOf_2"}
        ];

        const shouldNotInclude = [
            {"id": 200, "name": "role_byId"},
            {"id": 226, "name": "role_byAccessToAnyOf_1_Inactive"},
            {"id": 227, "name": "role_byAccessToAnyOf_1_Inactive-z_role_permission"},
        ];

        requestJson('/role/byAccessToAnyOf').then((output) => {
            shouldInclude.forEach(role => {
                expect(output).to.deep.include(role);
            });

            shouldNotInclude.forEach(role => {
                expect(output).to.not.deep.include(role);
            });
        });
    });

    it('should be possible to remove a role', () => {
        requestJson('/role/remove').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should not be able to interact with a removed role', () => {
        requestJson('/role/removeInteraction').then((output) => {
            expect(output).to.deep.equal({
                "found": false
            });
        });
    });

    it('should be possible to update a roles name', () => {
        requestJson('/role/update').then((output) => {
            expect(output).to.deep.equal({
                "id": 211,
                "name": "role_update_NewName"
            });
        });
    });

    it('should be possible to refresh a role`s Data', () => {
        requestJson('/role/refresh').then((output) => {
            expect(output).to.deep.equal({
                "id": 212,
                "name": "role_refresh_UpdatedName"
            });
        });
    });

    it('should show the correct permissions of a role', () => {
        requestJson('/role/getPermissions').then((output) => {
            expect(output).to.deep.equal(
                {"id": 213, "name": "role_getPermissions", "permissions":[
                    "role_getPermissions.should.have.1",
                    "role_getPermissions.should.have.2"
                ]}
            );
        });
    });

    it('should show the correct users of a role', () => {
        requestJson('/role/getUsers').then((output) => {
            expect(output).to.deep.equal(
                {"id": 214,"name": "role_getUsers", "users":[
                    {"id": 201, "name": "role_getUsers_1_Active@cypress.test"},
                    {"id": 202, "name": "role_getUsers_2_Active@cypress.test"}
                ]}
            );
        });
    });

    it('should be possible to create a new role', () => {
        requestJson('/role/add').then((output) => {
            expect(output).to.deep.equal({
                "createdRoleDirect": {"id": 230,"name": "role_add_NewRole"},
                "createdRoleGet": {"id": 230, "name": "role_add_NewRole"}
            });
        });
    });

    it('should check if a role has permissions to all specified permissions', () => {
        requestJson('/role/hasAccessToAll').then((output) => {
            expect(output).to.deep.equal({
                "hasAccessToAll_15": true,
                "hasAccessToAll_16": false
            });
        });
    });

    it('should check if a role has permissions to any of the specified permissions', () => {
        requestJson('/role/hasAccessToAnyOf').then((output) => {
            expect(output).to.deep.equal({
                "hasAccessToAnyOf_17": true,
                "hasAccessToAnyOf_18": false}
            );
        });
    });
});