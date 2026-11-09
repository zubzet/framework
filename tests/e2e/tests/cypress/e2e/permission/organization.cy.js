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
                "createdOrganizationDirect": {"id": 10000, "name": "org_add_NewOrganization"},
                "createdOrganizationGet": {"id": 10000, "name": "org_add_NewOrganization"}
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

    it('should return the linked group of an organization (getGroup)', () => {
        requestJson('/organization/getGroup').then((output) => {
            expect(output).to.deep.equal({
                "id": 512,
                "name": "org_getGroup",
                "group": {
                    "id": 240,
                    "name": "org_getGroup_Group"
                }
            });
        });
    });

    it('should return null when an organization has no linked group (getGroup)', () => {
        requestJson('/organization/getGroupNull').then((output) => {
            expect(output).to.deep.equal({
                "id": 513,
                "name": "org_getGroupNull",
                "group": null
            });
        });
    });

    it('should not create a group when add() is called without createGroup', () => {
        // The earlier `add` test in this file already consumed id 10000.
        requestJson('/organization/addWithoutGroup').then((output) => {
            expect(output).to.deep.equal({
                "id": 10001,
                "name": "org_addWithoutGroup_NewOrganization",
                "group": null
            });
        });
    });

    it('should create and link a group when add() is called with createGroup=true', () => {
        requestJson('/organization/addWithGroup').then((output) => {
            expect(output.organization.id).to.equal(10002);
            expect(output.organization.name).to.equal("org_addWithGroup_NewOrganization");
            expect(output.organization.group).to.not.equal(null);
            expect(output.organization.group.name).to.equal("org_addWithGroup_NewOrganization_Group");
            expect(output.groupHasOrgNameSuffix).to.equal(true);
        });
    });

    it('should add the new organization`s group when assigning an organization (User::updateOrganization)', () => {
        requestJson('/organization/userUpdateOrganizationGroupSyncAssign').then((output) => {
            expect(output).to.deep.equal({
                "groups": [
                    {"id": 241, "name": "org_userOrg_groupSync_Initial_Group"}
                ]
            });
        });
    });

    it('should swap the user`s group when changing organizations (User::updateOrganization)', () => {
        requestJson('/organization/userUpdateOrganizationGroupSyncChange').then((output) => {
            expect(output).to.deep.equal({
                "groups": [
                    {"id": 242, "name": "org_userOrg_groupSync_Updated_Group"}
                ]
            });
        });
    });

    it('should remove the user`s group when unsetting the organization (User::updateOrganization with null)', () => {
        requestJson('/organization/userUpdateOrganizationGroupSyncUnset').then((output) => {
            expect(output).to.deep.equal({
                "groups": []
            });
        });
    });

    // Seeded users: 560 is in org 505, 561 has no organization, 562 is in the removed org 501
    describe('membership', () => {
        it('hasMember is true for a member of the organization', () => {
            requestJson('/organization/hasMember/505/560').then((output) => expect(output).to.equal(true));
        });

        it('hasMember is false for a member of another organization or none', () => {
            requestJson('/organization/hasMember/506/560').then((output) => expect(output).to.equal(false));
            requestJson('/organization/hasMember/505/561').then((output) => expect(output).to.equal(false));
        });

        it('isMemberOf is true for the organization of the user', () => {
            requestJson('/organization/isMemberOf/560/505').then((output) => expect(output).to.equal(true));
        });

        it('isMemberOf is false for another organization or a user without one', () => {
            requestJson('/organization/isMemberOf/560/506').then((output) => expect(output).to.equal(false));
            requestJson('/organization/isMemberOf/561/505').then((output) => expect(output).to.equal(false));
            requestJson('/organization/isMemberOf/562/505').then((output) => expect(output).to.equal(false));
        });
    });

    // Request::requireOrganization, through the probe in OrganizationController
    describe('requireOrganization', () => {
        function expectDenied(page) {
            cy.request('/organization/requireBool').its('body').should('eq', 'denied');
            cy.request({ url: '/organization/require', failOnStatusCode: false }).its('body').then((body) => {
                expect(body, 'the default call stops the request').to.not.include('passed');
                expect(body).to.include(page);
            });
        }

        const forbidden = 'Page not for you';
        const login = 'data-test="btn-login"';

        beforeEach(() => cy.clearCookies());

        it('sends a logged out user to the login', () => {
            expectDenied(login);
        });

        it('allows a member of an organization', () => {
            cy.request('/organization/requireLogin/560');
            cy.request('/organization/requireBool').its('body').should('eq', 'allowed');
            cy.request('/organization/require').its('body').should('eq', 'passed');
        });

        it('forbids a user without an organization', () => {
            cy.request('/organization/requireLogin/561');
            expectDenied(forbidden);
        });

        it('forbids a member of a removed organization', () => {
            cy.request('/organization/requireLogin/562');
            expectDenied(forbidden);
        });
    });

    // Organizations hold permissions, roles and groups like a user, their members inherit them
    describe('permissions', () => {
        it('should check the permissions of an organization (hasAccessAll, hasAccessAnyOf)', () => {
            requestJson('/organization/hasAccess').then((output) => {
                expect(output).to.deep.equal({
                    "all": true,
                    "allWithMissing": false,
                    "anyOf": true,
                    "anyOfInactive": false,
                    "wildcard": true,
                    "wildcardOutside": false
                });
            });
        });

        it('should list the own permissions and those of roles and groups once (getPermissions)', () => {
            const expected = [
                "orgperm.getPermissions.direct",
                "orgperm.getPermissions.shared",
                "orgperm.getPermissions.role",
                "orgperm.getPermissions.group"
            ];

            requestJson('/organization/getPermissions').then((output) => {
                expect(output).to.have.members(expected);
                expect(output).to.have.length(expected.length);
            });
        });

        it('should return the active roles and groups of an organization separately (getRoles, getGroups)', () => {
            requestJson('/organization/getRoles').then((output) => {
                expect(output.roles).to.have.members(["orgperm_getRoles_RoleA", "orgperm_getRoles_RoleB"]);
                expect(output.roles).to.have.length(2);
                expect(output.groups).to.deep.equal(["orgperm_getRoles_Group"]);
            });
        });

        it('should return the active roles and groups of an organization separately (Role::byOrganization, Group::byOrganization)', () => {
            requestJson('/organization/byOrganization').then((output) => {
                expect(output.roles).to.have.members(["orgperm_getRoles_RoleA", "orgperm_getRoles_RoleB"]);
                expect(output.roles).to.have.length(2);
                expect(output.groups).to.deep.equal(["orgperm_getRoles_Group"]);
            });
        });

        it('should return the active organizations holding a role or group (Organization::byRole, Organization::byGroup)', () => {
            requestJson('/organization/byRole').then((output) => {
                expect(output.byRole).to.have.members(["orgperm_byRole_RoleAndGroup", "orgperm_byRole_RoleOnly"]);
                expect(output.byRole).to.have.length(2);
                expect(output.byGroup).to.deep.equal(["orgperm_byRole_RoleAndGroup"]);
            });
        });

        it('should return organizations having access to all specified permissions (Organization::byAccessToAll)', () => {
            requestJson('/organization/byAccessToAll').then((output) => {
                expect(output).to.deep.equal(["orgperm_byAccess_DirectAndRole"]);
            });
        });

        it('should return organizations having access to any of the specified permissions (Organization::byAccessToAnyOf)', () => {
            const expected = [
                "orgperm_byAccess_DirectAndRole",
                "orgperm_byAccess_DirectOnly",
                "orgperm_byAccess_RoleOnly"
            ];

            requestJson('/organization/byAccessToAnyOf').then((output) => {
                expect(output).to.have.members(expected);
                expect(output).to.have.length(expected.length);
            });
        });
    });

    describe('inheritance of members', () => {
        it('should let members inherit the permissions of their organization (hasAccessAll, hasAccessAnyOf)', () => {
            requestJson('/organization/inheritDirect').then((output) => {
                expect(output.memberAll).to.equal(true);
                expect(output.memberAllWithMissing).to.equal(false);
                expect(output.memberAnyOf).to.equal(true);
                expect(output.memberAnyOfInactive).to.equal(false);
                expect(output.memberWildcard).to.equal(true);

                // A permission held by the user and the organization shows up once
                const expected = [
                    {"name": "orgperm.direct.read"},
                    {"name": "orgperm.direct.write"},
                    {"name": "orgperm.wild.*"}
                ];
                expect(output.memberPermissions).to.have.deep.members(expected);
                expect(output.memberPermissions).to.have.length(expected.length);
            });
        });

        it('should not give the permissions of an organization to non-members (hasAccessAll, hasAccessAnyOf)', () => {
            requestJson('/organization/inheritDirect').then((output) => {
                expect(output.noneAll).to.equal(false);
                expect(output.noneAnyOf).to.equal(false);
                expect(output.otherAnyOf).to.equal(false);
                expect(output.otherPermissions).to.deep.equal([{"name": "orgperm.direct.other"}]);
            });
        });

        it('should let members inherit the permissions of the roles and groups of their organization (getPermissions)', () => {
            requestJson('/organization/inheritRoleGroup').then((output) => {
                const expected = [
                    {"name": "orgperm.viaRole.perm"},
                    {"name": "orgperm.viaGroup.perm"}
                ];

                expect(output.memberAll).to.equal(true);
                expect(output.memberPermissions).to.have.deep.members(expected);
                expect(output.memberPermissions).to.have.length(expected.length);
                expect(output.noneAnyOf).to.equal(false);
                expect(output.nonePermissions).to.deep.equal([]);
            });
        });

        it('should include the roles and groups of the organization once (Role::byUser, Group::byUser)', () => {
            const roles = ["orgperm_byUser_SharedRole", "orgperm_byUser_DirectRole", "orgperm_byUser_OrganizationRole"];
            const groups = ["orgperm_byUser_SharedGroup", "orgperm_byUser_DirectGroup", "orgperm_byUser_OrganizationGroup"];

            requestJson('/organization/userByRoleGroup').then((output) => {
                expect(output.roles).to.have.members(roles);
                expect(output.roles).to.have.length(roles.length);
                expect(output.groups).to.have.members(groups);
                expect(output.groups).to.have.length(groups.length);
                expect(output.userRoles).to.have.members(roles);
                expect(output.userRoles).to.have.length(roles.length);
                expect(output.userGroups).to.have.members(groups);
                expect(output.userGroups).to.have.length(groups.length);
            });
        });

        it('should return the members of an organization holding the role or group once (User::byRole, User::byGroup, Role::getUsers)', () => {
            const expected = [
                "orgperm_users_member@cypress.test",
                "orgperm_users_memberAndDirect@cypress.test",
                "orgperm_users_direct@cypress.test"
            ];

            requestJson('/organization/usersByRoleGroup').then((output) => {
                ['byRole', 'byGroup', 'roleGetUsers', 'groupGetUsers'].forEach((key) => {
                    expect(output[key]).to.have.members(expected);
                    expect(output[key]).to.have.length(expected.length);
                });

                // Members of another organization only get the roles of that one
                expect(output.otherRole).to.deep.equal(["orgperm_users_otherOrganization@cypress.test"]);
            });
        });

        it('should return users having access to all specified permissions through their organization (User::byAccessToAll)', () => {
            const shouldInclude = [
                "orgperm_userAccess_direct@cypress.test",
                "orgperm_userAccess_role@cypress.test",
                "orgperm_userAccess_both@cypress.test"
            ];

            const shouldNotInclude = [
                "orgperm_userAccess_none@cypress.test",
                "orgperm_userAccess_partial@cypress.test",
                "orgperm_userAccess_inactiveOrganization@cypress.test",
                "orgperm_userAccess_inactiveUser@cypress.test"
            ];

            requestJson('/organization/userByAccessToAll').then((output) => {
                shouldInclude.forEach(email => expect(output).to.include(email));
                shouldNotInclude.forEach(email => expect(output).to.not.include(email));
            });
        });

        it('should return users having access to any of the specified permissions through their organization (User::byAccessToAnyOf)', () => {
            const shouldInclude = [
                "orgperm_userAccess_direct@cypress.test",
                "orgperm_userAccess_role@cypress.test",
                "orgperm_userAccess_both@cypress.test",
                "orgperm_userAccess_none@cypress.test",
                "orgperm_userAccess_partial@cypress.test"
            ];

            const shouldNotInclude = [
                "orgperm_userAccess_inactiveOrganization@cypress.test",
                "orgperm_userAccess_inactiveUser@cypress.test"
            ];

            requestJson('/organization/userByAccessToAnyOf').then((output) => {
                shouldInclude.forEach(email => expect(output).to.include(email));
                shouldNotInclude.forEach(email => expect(output).to.not.include(email));
            });
        });

        it('should grant nothing for an inactive organization', () => {
            requestJson('/organization/inactiveOrganization').then((output) => {
                expect(output).to.deep.equal({
                    "permissions": [],
                    "anyOf": false,
                    "roles": [],
                    "users": [],
                    "organizations": []
                });
            });
        });

        it('should keep the permissions of users without an organization and of members of an empty one', () => {
            const permissions = [
                {"name": "orgperm.regression.direct"},
                {"name": "orgperm.regression.role"}
            ];

            requestJson('/organization/noOrganization').then((output) => {
                expect(output.nonePermissions).to.have.deep.members(permissions);
                expect(output.nonePermissions).to.have.length(permissions.length);
                expect(output.noneRoles).to.deep.equal(["orgperm_regression_Role"]);
                expect(output.noneAll).to.equal(true);
                expect(output.memberPermissions).to.have.deep.members(permissions);
                expect(output.memberPermissions).to.have.length(permissions.length);
                expect(output.memberRoles).to.deep.equal(["orgperm_regression_Role"]);
                expect(output.byRole).to.have.members([
                    "orgperm_regression_none@cypress.test",
                    "orgperm_regression_member@cypress.test"
                ]);
                expect(output.byRole).to.have.length(2);
            });
        });
    });

    describe('changing what an organization holds', () => {
        it('should be possible to add and remove permissions of an organization (permissionsAdd, permissionsRemove)', () => {
            requestJson('/organization/mutatePermissions').then((output) => {
                expect(output.before).to.equal(false);

                expect(output.added.organization).to.have.members([
                    "orgperm.mutatePermissions.perm",
                    "orgperm.mutatePermissions.other"
                ]);
                expect(output.added.organization).to.have.length(2);
                expect(output.added.member).to.have.deep.members([
                    {"name": "orgperm.mutatePermissions.perm"},
                    {"name": "orgperm.mutatePermissions.other"}
                ]);
                expect(output.added.member).to.have.length(2);

                // *.* matches every permission for the members until it is removed
                expect(output.wildcard).to.equal(true);
                expect(output.wildcardRemoved).to.equal(false);

                expect(output.removed).to.deep.equal({
                    "organization": ["orgperm.mutatePermissions.other"],
                    "member": [{"name": "orgperm.mutatePermissions.other"}],
                    "memberAccess": false
                });
            });
        });

        it('should be possible to add and remove roles of an organization (rolesAdd, rolesRemove)', () => {
            requestJson('/organization/mutateRoles').then((output) => {
                expect(output).to.deep.equal({
                    "before": false,
                    "added": {
                        "organization": ["orgperm_mutateRoles_Role"],
                        "memberRoles": ["orgperm_mutateRoles_Role"],
                        "memberAccess": true
                    },
                    "removed": {
                        "organization": [],
                        "memberRoles": [],
                        "memberAccess": false
                    }
                });
            });
        });

        it('should be possible to add and remove groups of an organization (groupsAdd, groupsRemove)', () => {
            requestJson('/organization/mutateGroups').then((output) => {
                expect(output).to.deep.equal({
                    "before": false,
                    "added": {
                        "organization": ["orgperm_mutateGroups_Group"],
                        "memberGroups": ["orgperm_mutateGroups_Group"],
                        "memberAccess": true
                    },
                    "removed": {
                        "organization": [],
                        "memberGroups": [],
                        "memberAccess": false
                    }
                });
            });
        });

        it('should move the inherited permissions with a member to another organization (User::updateOrganization)', () => {
            requestJson('/organization/userMoveOrganization').then((output) => {
                expect(output).to.deep.equal({
                    "before": [{"name": "orgperm.move.from"}],
                    "moved": [{"name": "orgperm.move.to"}],
                    "unset": []
                });
            });
        });

        it('should end the inheritance of the members when the organization is removed (remove)', () => {
            requestJson('/organization/removeOrganization').then((output) => {
                expect(output.before.permissions).to.have.deep.members([
                    {"name": "orgperm.remove.direct"},
                    {"name": "orgperm.remove.role"}
                ]);
                expect(output.before.permissions).to.have.length(2);
                expect(output.before.roles).to.deep.equal(["orgperm_remove_Role"]);
                expect(output.after).to.deep.equal({
                    "permissions": [],
                    "roles": []
                });
            });
        });

        it('should stop granting the permissions of a role that is removed (Role::remove)', () => {
            requestJson('/organization/roleRemoved').then((output) => {
                expect(output).to.deep.equal({
                    "before": {
                        "organization": ["orgperm_roleRemoved_Role"],
                        "memberAccess": true
                    },
                    "after": {
                        "organization": [],
                        "memberRoles": [],
                        "memberAccess": false
                    }
                });
            });
        });
    });

    // Request::checkPermission and Authentication\User::checkPermissionOf, through legacyCheck in OrganizationController
    describe('legacy checkPermission', () => {
        beforeEach(() => cy.clearCookies());

        it('sees the permissions of the organization of a member', () => {
            cy.request('/organization/requireLogin/594');
            requestJson('/organization/legacyCheck').then((output) => {
                expect(output).to.deep.equal({
                    "direct": true,
                    "role": true,
                    "missing": false,
                    "ofMember": true,
                    "ofNone": false
                });
            });
        });

        it('does not see them for a user without an organization', () => {
            cy.request('/organization/requireLogin/595');
            requestJson('/organization/legacyCheck').then((output) => {
                expect(output).to.deep.equal({
                    "direct": false,
                    "role": false,
                    "missing": false,
                    "ofMember": true,
                    "ofNone": false
                });
            });
        });
    });
});
