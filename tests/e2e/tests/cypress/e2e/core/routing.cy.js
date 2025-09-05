describe('Routing', () => {
    before(() => {
        cy.dbSeed();
    });

    const cases = [
        // Default cases
        {
            route: "/test",
            expected: "TestRoute ExecutedArray ( )"
        },
        {   // Check if the route of the other file is imported correctly
            route: "/test2",
            expected: "TestRoute ExecutedArray ( )"
        },
        {   // check if Route Parameters are working
            route: "/abc/U13/P12",
            expected: "TestRoute ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
        {   // First Middleware accepts the request and the second one blocks it
            route: "/rm-accept/rm-block",
            expected: "Route Middleware Accept ExecutedArray ( ) Route Middleware Blocked ExecutedArray ( ) "
        },
        // Without Group
        {
            route: "/test",
            expected: "TestRoute ExecutedArray ( )"
        },
        {
            route: "/middleware-accept",
            expected: "Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) "
        },
        {
            route: "/middleware-block",
            expected: "Route Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/afterware",
            expected: "TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) "
        },
        {
            route: "/middleware-accept-afterware",
            expected: "Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) "
        },
        {
            route: "/middleware-block-afterware",
            expected: "Route Middleware Blocked ExecutedArray ( ) "
        },

        // With /accept Group
        {
            route: "/accept/test",
            expected: "Group Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( )"
        },
        {
            route: "/accept/middleware-accept",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) "
        },
        {
            route: "/accept/middleware-block",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/accept/afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) "
        },
        {
            route: "/accept/middleware-accept-afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) "
        },
        {
            route: "/accept/middleware-block-afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Blocked ExecutedArray ( ) "
        },

        // With /block Group
        {
            route: "/block/test",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block/middleware-accept",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block/middleware-block",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block/afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block/middleware-accept-afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block/middleware-block-afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },

        // With /afterware Group
        {
            route: "/afterware/test",
            expected: "TestRoute ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/afterware/middleware-accept",
            expected: "Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/afterware/middleware-block",
            expected: "Route Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/afterware/afterware",
            expected: "TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/afterware/middleware-accept-afterware",
            expected: "Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/afterware/middleware-block-afterware",
            expected: "Route Middleware Blocked ExecutedArray ( ) "
        },

        // With /accept-afterware Group
        {
            route: "/accept-afterware/test",
            expected: "Group Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/accept-afterware/middleware-accept",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Group Afterware ExecutedArray ( )"
        },
        {
            route: "/accept-afterware/middleware-block",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/accept-afterware/afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/accept-afterware/middleware-accept-afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Accept ExecutedArray ( ) TestRoute ExecutedArray ( ) Route Afterware ExecutedArray ( ) Group Afterware ExecutedArray ( ) "
        },
        {
            route: "/accept-afterware/middleware-block-afterware",
            expected: "Group Middleware Accept ExecutedArray ( ) Route Middleware Blocked ExecutedArray ( ) "
        },

        // With /block-afterware Group
        {
            route: "/block-afterware/test",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block-afterware/middleware-accept",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block-afterware/middleware-block",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block-afterware/afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block-afterware/middleware-accept-afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },
        {
            route: "/block-afterware/middleware-block-afterware",
            expected: "Group Middleware Blocked ExecutedArray ( ) "
        },

        // With /accept-afterware-parameters Group
        {
            route: "/accept-afterware-parameters/U13/P12/test",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) TestRoute ExecutedArray ( [userId] => U13 [postId] => P12 ) Group Afterware ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
        {
            route: "/accept-afterware-parameters/U13/P12/middleware-accept",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) TestRoute ExecutedArray ( [userId] => U13 [postId] => P12 ) Group Afterware ExecutedArray ( [userId] => U13 [postId] => P12 )"
        },
        {
            route: "/accept-afterware-parameters/U13/P12/middleware-block",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Middleware Blocked ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
        {
            route: "/accept-afterware-parameters/U13/P12/afterware",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) TestRoute ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Afterware ExecutedArray ( [userId] => U13 [postId] => P12 ) Group Afterware ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
        {
            route: "/accept-afterware-parameters/U13/P12/middleware-accept-afterware",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) TestRoute ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Afterware ExecutedArray ( [userId] => U13 [postId] => P12 ) Group Afterware ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
        {
            route: "/accept-afterware-parameters/U13/P12/middleware-block-afterware",
            expected: "Group Middleware Accept ExecutedArray ( [userId] => U13 [postId] => P12 ) Route Middleware Blocked ExecutedArray ( [userId] => U13 [postId] => P12 ) "
        },
    ];

    it('should check the Routing-System with all routes', () => {
        cases.forEach(({ route, expected }) => {
            cy.request('GET', route).then((response) => {
                expect(response.status).to.eq(200);

                let actual = response.body.replace(/\s+/g, ' ').trim();
                expected = expected.replace(/\s+/g, ' ').trim();

                expect(actual).to.equals(expected);
            });
        });
    });

    it("should throw an error cause of false method", () => {
        cy.request({
            method: 'POST',
            url: '/test',
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.eq(405);
        });
    });


    it("should check if POST method is working", () => {
        cy.request({
            method: 'POST',
            url: '/post-test'
        }).then((response) => {
            expect(response.status).to.eq(200);

            let actual = response.body.replace(/\s+/g, ' ').trim();
            let expected = "TestRoute ExecutedArray ( )".replace(/\s+/g, ' ').trim();

            expect(actual).to.equals(expected);
        });
    });

    it("should check if ANY method is working", () => {
        const cases = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];

        cases.forEach((method) => {
            cy.request({
                method: method,
                url: '/any-test'
            }).then((response) => {
                expect(response.status).to.eq(200);

                let actual = response.body.replace(/\s+/g, ' ').trim();
                let expected = "TestRoute ExecutedArray ( )".replace(/\s+/g, ' ').trim();

                expect(actual).to.equals(expected);
            });
        });
    });

    it("should check if DEFINE method is working", () => {
        cy.request({
            method: 'GET',
            url: '/define-get'
        }).then((response) => {
            expect(response.status).to.eq(200);

            let actual = response.body.replace(/\s+/g, ' ').trim();
            let expected = "TestRoute ExecutedArray ( )".replace(/\s+/g, ' ').trim();

            expect(actual).to.equals(expected);
        });

        cy.request({
            method: 'POST',
            url: '/define-get',
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.eq(405);
        });
    });
});