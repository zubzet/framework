describe('Migration System - Status', () => {

    it("should display the migration status correctly", () => {
        cy.exec("docker exec application php index.php db:status").then((result) => {
            expect(result.stdout).to.include("Migration Lock Status: UNLOCKED");
        });
    });

});