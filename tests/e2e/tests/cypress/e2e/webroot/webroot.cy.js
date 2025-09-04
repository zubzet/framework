describe('Webroot Directory', () => {
    before(() => {
        cy.dbSeed();
    });


    const cases = [
        {
            path: "/accessible.txt",
            status: 200,
            content: "Should be accessible."
        },
        {
            path: "/assets/assets.txt",
            status: 200,
            content: `A file inside the assets directory.`
        },
        {
            path: "/assets",
            status: 404,
        },
        {
            path: "/app/Controllers/AdminController.php",
            status: 404
        },
        {
            path: "/composer.json",
            status: 404
        },
        {
            path: "/z_config/z_settings.ini",
            status: 404
        }
    ];

    it('Check Access to files', () => {

        cases.forEach(item => {
            cy.request({
                url: item.path,
                failOnStatusCode: false
            }).then((response) => {
                expect(response.status).to.eq(item.status);

                if(item.status !== 200) return;

                expect(response.body).to.contain(item.content);
            });
        });

    });
});