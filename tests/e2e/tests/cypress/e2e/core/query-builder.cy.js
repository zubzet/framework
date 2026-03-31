describe('Query Builder', () => {
    before(() => {
        cy.dbSeed();
    });

    const cases = [
        {
            route: '/Core/querybuilderSelectWhereExtended',
            expected: [
                {"id":1, "email":"admin@zierhut-it.de"}
            ]
        },
        {
            route: '/Core/querybuilderSelectJoin',
            expected: [
                {"id":1, "email":"admin@zierhut-it.de", "name":"Admin"}
            ]
        },
        {
            route: '/Core/querybuilderSelectLike',
            expected: [
                {"id":1,"email":"admin@zierhut-it.de"}
            ]
        },
        {
            route: '/Core/querybuilderSelectLT',
            expected: [
                {"id":1,"email":"admin@zierhut-it.de"},
                {"id":2,"email":"support@zierhut-it.de"}
            ]
        },
        {
            route: '/Core/querybuilderSelectIn',
            expected: [
                {"id":1,"email":"admin@zierhut-it.de"},
                {"id":2,"email":"support@zierhut-it.de"}
            ]
        },
        {
            route: '/Core/querybuilderSelectORAND',
            expected: [
                {"id":1,"email":"admin@zierhut-it.de"},
                {"id":2,"email":"support@zierhut-it.de"}
            ]
        },
        {
            route: '/Core/querybuilderSelectLimit',
            expected: [
                {"id":3,"email":"customer@zierhut-it.de"},
                {"id":4,"email":"not-activated@domain.de"}
            ]
        },
        {
            route: '/Core/queryBuilderInsert',
            expected: [
                {"id":1,"name":"TestName1","value":123},
                {"id":2,"name":"TestName2","value":456},
                {"id":3,"name":"TestName3","value":789}
            ]
        },
        {
            route: '/Core/queryBuilderUpdate',
            expected: {"id":1,"name":"UpdatedTestLanguage1","nativeName":"UpdatedTestLanguageNative1","value":"utl1"}
        },
        {
            route: '/Core/queryBuilderDelete',
            expected: {
                "null":"null"
            }
        }
    ];


    it('Query Builder', () => {
        cases.forEach(({ route, expected }) => {
            cy.request('GET', route).then((response) => {
                expect(response.status).to.eq(200);

                let body = JSON.parse(response.body);

                expect(body).to.deep.equal(expected);
            });
        });
    });

    it('CakePHP ValueBinder compatibility — class and method signatures are intact', () => {
        cy.request('GET', '/Core/queryBuilderCakePHPCompat').then((response) => {
            expect(response.status).to.eq(200);

            const body = JSON.parse(response.body);

            // All checks must pass for ZubZetValueBinder to work correctly
            expect(body.compatible, JSON.stringify(body.checks, null, 2)).to.eq(true);

            // Verify individual checks explicitly so failures are easy to read
            expect(body.checks.class_exists, 'Cake\\Database\\ValueBinder class must exist').to.eq(true);
            expect(body.checks.method_placeholder, 'ValueBinder::placeholder() must exist').to.eq(true);
            expect(body.checks.method_bind, 'ValueBinder::bind() must exist').to.eq(true);
            expect(body.checks.method_generateManyNamed, 'ValueBinder::generateManyNamed() must exist').to.eq(true);
            expect(body.checks.method_bindings, 'ValueBinder::bindings() must exist').to.eq(true);
            expect(body.checks.property__bindings, 'ValueBinder::$_bindings property must exist').to.eq(true);

            // Signature checks
            expect(body.checks.placeholder_param_count_1, 'placeholder() must take exactly 1 param').to.eq(true);
            expect(body.checks.placeholder_param_is_token, 'placeholder() param must be named $token').to.eq(true);
            expect(body.checks.placeholder_returns_string, 'placeholder() must return string').to.eq(true);
            expect(body.checks.bind_has_at_least_2_params, 'bind() must have at least 2 params ($param, $value)').to.eq(true);
            expect(body.checks.generateManyNamed_has_values_param, 'generateManyNamed() first param must be named $values').to.eq(true);
            expect(body.checks.generateManyNamed_returns_array, 'generateManyNamed() must return array').to.eq(true);
            expect(body.checks.bindings_returns_array, 'bindings() must return array').to.eq(true);
        });
    });

    it('SQL injection via named placeholder (:c0) is not executed', () => {
        cy.request({
            method: 'GET',
            url: '/Core/queryBuilderInjectionTest',
            failOnStatusCode: false,
        }).then((response) => {
            expect(response.status).to.eq(200);
            const body = JSON.parse(response.body);
            expect(body.count).to.eq(0);
        });
    });
});