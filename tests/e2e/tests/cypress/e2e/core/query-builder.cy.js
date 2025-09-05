describe('Query Builder', () => {
    before(() => {
        cy.dbSeed();
    });


    const cases = [
        {
            route: '/Core/querybuilderSelect',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) [1] => Array ( [id] => 2 [email] => support@zierhut-it.de ) [2] => Array ( [id] => 3 [email] => customer@zierhut-it.de ) [3] => Array ( [id] => 4 [email] => not-activated@domain.de ) [4] => Array ( [id] => 5 [email] => customer-new@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectWhere',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectWhereExtended',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectJoin',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de [name] => Admin ) ) "
        },
        {
            route: '/Core/querybuilderSelectLike',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectLT',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) [1] => Array ( [id] => 2 [email] => support@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectIn',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) [1] => Array ( [id] => 2 [email] => support@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectORAND',
            expected: "Array ( [0] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) [1] => Array ( [id] => 2 [email] => support@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectLimit',
            expected: "Array ( [0] => Array ( [id] => 3 [email] => customer@zierhut-it.de ) [1] => Array ( [id] => 4 [email] => not-activated@domain.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectOrder',
            expected: "Array ( [0] => Array ( [id] => 5 [email] => customer-new@zierhut-it.de ) [1] => Array ( [id] => 4 [email] => not-activated@domain.de ) [2] => Array ( [id] => 3 [email] => customer@zierhut-it.de ) [3] => Array ( [id] => 2 [email] => support@zierhut-it.de ) [4] => Array ( [id] => 1 [email] => admin@zierhut-it.de ) ) "
        },
        {
            route: '/Core/querybuilderSelectGroup',
            expected: "Array ( [0] => Array ( [COUNT(*)] => 4 ) ) "
        },
        {
            route: '/Core/queryBuilderInsert',
            expected: "Array ( [id] => 3 [name] => TestLanguage1 [nativeName] => TestLanguageNative1 [value] => tl1 ) Array ( [id] => 4 [name] => TestLanguage2 [nativeName] => TestLanguageNative2 [value] => tl2 ) "
        },
        {
            route: '/Core/queryBuilderUpdate',
            expected: "Array ( [id] => 1 [name] => UpdatedTestLanguage1 [nativeName] => UpdatedTestLanguageNative1 [value] => utl1 ) "
        },
        {
            route: '/Core/queryBuilderDelete',
            expected: ""
        }
    ];


    it('Query Builder', () => {
        cases.forEach(({ route, expected }) => {
            cy.request('GET', route).then((response) => {
                expect(response.status).to.eq(200);

                let actual = response.body.replace(/\s+/g, ' ').trim();
                expected = expected.replace(/\s+/g, ' ').trim();

                expect(actual).to.equals(expected);
            });
        });
    });
});