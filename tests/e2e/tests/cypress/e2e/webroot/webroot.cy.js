describe('Webroot Directory', () => {
    before(() => {
        cy.dbSeed();
    });


    const cases = [
        {
            path: "/AccessFile.txt",
            status: 200,
            content: "Test File for testing the webroot directory"
        },
        {
            path: "/assets/css/main.css",
            status: 200,
            content: `body { background-color: red; }`
        },
        {
            path: "/assets/js/main.js",
            status: 200,
            content: `console.log("Test");`
        },
        {
            path: "/app/Controllers/AdminController.php",
            status: 404
        },
        {
            path: "/packaging/docker/Dockerfile.apache-local",
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