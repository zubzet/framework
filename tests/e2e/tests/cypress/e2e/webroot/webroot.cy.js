describe('Webroot Directory', () => {
    before(() => {
        cy.dbSeed();
    });

    after(() => {
        cy.exec("rm -f ../webroot/uploads/probe.php");
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

    it('Serves an uploaded php file without running it', () => {
        cy.writeFile("../webroot/uploads/probe.php", "<?php echo 'php-' . 'executed'; ?>");

        cy.request("/uploads/probe.php").then((response) => {
            expect(response.body).to.contain("<?php");
            expect(response.body).not.to.contain("php-executed");
        });
    });
});