describe('Permission System - User', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }


    it('should return user (byId)', () => {
        requestJson('/user/byId').then((output) => {
            expect(output).to.deep.equal({
                id: 100,
                email: 'user_byId_Active@cypress.test',
                isVerified: true,
                verified: '2000-01-01 12:00:00',
            });
        });
    });

    it('should not return user (byId) cause of inactive status', () => {
        requestJson('/user/byIdInactive').then((output) => {
            expect(output).to.deep.equal({
                found: false
            });
        });
    });


    it('should return user (byEmail)', () => {
        requestJson('/user/byEmail').then((output) => {
            expect(output).to.deep.equal({
                id: 102,
                email: 'user_byEmail_Active@cypress.test',
                isVerified: true,
                verified: '2000-01-01 12:00:00',
            });
        });
    });

    it('should not return user (byEmail) cause of inactive status', () => {
        requestJson('/user/byEmailInactive').then((output) => {
            expect(output).to.deep.equal({
                found: false,
            });
        });
    });

    it('should return users (byNotVerified)', () => {
        const shouldInclude = [
            {"id":106,"email":"user_byNotVerified_3_Active@cypress.test","isVerified":false,"verified":"2029-01-01 12:00:00"},
            {"id":107,"email":"user_byNotVerified_4_Active@cypress.test","isVerified":false,"verified":"2030-01-01 12:00:00"},
            {"id":108,"email":"user_byNotVerified_5_Active@cypress.test","isVerified":false,"verified":null},
        ];

        const shouldNotInclude = [
            {"id":100,"email":"user_byId_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":102,"email":"user_byEmail_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        requestJson('/user/byNotVerified').then((output) => {
            shouldInclude.forEach(user => {
                expect(output).to.deep.include(user);
            });

            shouldNotInclude.forEach(user => {
                expect(output).to.not.deep.include(user);
            });
        });
    });

    it('should return users (byRole)', () => {
        const expected = [
            {"id":111,"email":"user_byRole_1_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00","roles":[{"id":100,"name":"user_byRole"}]},
            {"id":112,"email":"user_byRole_2_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00","roles":[{"id":100,"name":"user_byRole"}]},
            {"id":113,"email":"user_byRole_3_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00","roles":[{"id":100,"name":"user_byRole"}]}
        ];

        requestJson('/user/byRole').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should return users (byIds)', () => {
        const expected = [
            {"id":127,"email":"user_byIds_1_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":128,"email":"user_byIds_2_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":129,"email":"user_byIds_3_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        requestJson('/user/byIds').then((output) => {
            expect(output).to.have.deep.members(expected);
            expect(output).to.have.length(expected.length);
        });
    });

    it('should return users (byAccessToAll)', () => {
        const shouldInclude = [
            {"id":132,"email":"user_byAccessToAll_1_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":133,"email":"user_byAccessToAll_2_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":134,"email":"user_byAccessToAll_3_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        const shouldNotInclude = [
            {"id":100,"email":"user_byId_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":102,"email":"user_byEmail_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        requestJson('/user/byAccessToAll').then((output) => {
            console.log(output);

            shouldInclude.forEach(user => {
                expect(output).to.deep.include(user);
            });

            shouldNotInclude.forEach(user => {
                expect(output).to.not.deep.include(user);
            });
        });
    });

    it('should return users (byAccessToAny)', () => {
        const shouldInclude = [
            {"id":139,"email":"user_byAccessToAnyOf_1_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":140,"email":"user_byAccessToAnyOf_2_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":141,"email":"user_byAccessToAnyOf_3_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":142,"email":"user_byAccessToAnyOf_4_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        const shouldNotInclude = [
            {"id":100,"email":"user_byId_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"},
            {"id":102,"email":"user_byEmail_Active@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00"}
        ];

        requestJson('/user/byAccessToAnyOf').then((output) => {
            shouldInclude.forEach(user => {
                expect(output).to.deep.include(user);
            });

            shouldNotInclude.forEach(user => {
                expect(output).to.not.deep.include(user);
            });
        });
    });

    it('should successfully remove a user', () => {
        requestJson('/user/remove').then((output) => {
            expect(output).to.deep.equal({
                found: false,
            });
        });
    });

    it('should not be possible to interact to removed users', () => {
        requestJson('/user/removeInteraction').then((output) => {
            expect(output).to.deep.equal({
                found: false,
            });
        });
    });

    it('should successfully update user email', () => {
        requestJson('/user/updateEmail').then((output) => {
            expect(output).to.deep.equal({
                id: 118,
                email: 'user_updateEmail-updated@update.cypress.test',
                isVerified: true,
                verified: '2000-01-01 12:00:00',
            });
        });
    });

    it('should successfully update a user password', () => {
        requestJson('/user/updatePassword').then((output) => {
            expect(output).to.deep.equal({
                isOldPasswordCorrect: true,
                isOldPasswordCorrectAfterUpdate: false,
                isNewPasswordCorrect: true
            });
        });
    });

    it('should successfully verify a user', () => {
        requestJson('/user/verify').then((output) => {
            expect(output.beforeVerified).to.deep.equal(
                {"id":120,"email":"user_verify@cypress.test","isVerified":false,"verified":null}
            );

            expect(output.afterVerified.verified).to.not.equal(null);
        });
    });

    it('should successfully verify a user with a specific date', () => {
        requestJson('/user/verifySpecific').then((output) => {
            expect(output).to.deep.equal(
                {"beforeVerified":{"id":121,"email":"user_verifySpecific@cypress.test","isVerified":false,"verified":null},"afterVerified":{"id":121,"email":"user_verifySpecific@cypress.test","isVerified":true,"verified":"2023-01-01 12:00:00"}}
            );
        });
    });


    it('should check if a user is verified, when verified is null', () => {
        requestJson('/user/isVerifiedOnNull').then((output) => {
            expect(output).to.deep.equal(
                {"isVerifiedNow":false,"isVerifiedPast":false}
            );
        });
    });

    it('should check if a user is verified, when verified is not null', () => {
        requestJson('/user/isVerifiedNotNull').then((output) => {
            expect(output).to.deep.equal(
                {"isVerifiedNow":true,"isVerifiedPast":false,"isVerifiedFuture":true}
            );
        });
    });

    it('should check if a user can be refreshed', () => {
        requestJson('/user/refresh').then((output) => {
            expect(output).to.deep.equal(
                {"id":124,"email":"user_refresh-updated@update.cypress.test","isVerified":true,"verified":"2025-01-01 12:00:00"}
            );
        });
    });

    it('should check if a user got the right roles', () => {
        requestJson('/user/getRoles').then((output) => {
            expect(output).to.deep.equal(
                {"id":125,"email":"user_getRoles@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00","roles":[{"id":101,"name":"user_getRoles_1_Active"},{"id":102,"name":"user_getRoles_2_Active"}]}
            );
        });
    });

    it('should check if a user got the right permissions', () => {
        requestJson('/user/getPermissions').then((output) => {
            expect(output).to.deep.equal(
                {"id":126,"email":"user_getPermissions@cypress.test","isVerified":true,"verified":"2000-01-01 12:00:00","permissions":[{"name":"user_getPermissions_1_Active-z_user_permission.1"},{"name":"user_getPermissions_2_Active-z_user_permission.1"},{"name":"user_getPermissions_1_Active.should.have.1"},{"name":"user_getPermissions_1_Active.should.have.2"},{"name":"user_getPermissions_2_Active.should.have.1"},{"name":"user_getPermissions_2_Active.should.have.2"}]}
            );
        });
    });

    it('should check if a user can be added', () => {
        requestJson('/user/add').then((output) => {
            expect(output).to.deep.equal(
                {"createdUserDirect":{"id":205,"email":"user_add@cypress.test","isVerified":true,"verified":"2005-01-01 00:00:00"},"createdUserGet":{"id":205,"email":"user_add@cypress.test","isVerified":true,"verified":"2005-01-01 00:00:00"},"passwordWorking":true}
            );
        });
    });

    it('should check if a user hasAccessToAll', () => {
        requestJson('/user/hasAccessToAll').then((output) => {
            expect(output).to.deep.equal(
                {"hasAccess_50":true,"hasAccess_51":false}
            );
        });
    });

    it('should check if a user hasAccessToAny', () => {
        requestJson('/user/hasAccessToAnyOf').then((output) => {
            expect(output).to.deep.equal(
                {"hasAccess_52":true,"hasAccess_53":false}
            );
        });
    });
});