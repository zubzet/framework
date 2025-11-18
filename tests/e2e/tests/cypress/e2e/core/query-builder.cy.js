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
            expected: {"1":null,"2":{"id":3,"name":"TestLanguage1","nativeName":"TestLanguageNative1","value":"tl1"},"3":{"id":4,"name":"TestLanguage2","nativeName":"TestLanguageNative2","value":"tl2"}}
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

});