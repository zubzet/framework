// Asserts that the custom DBAL TimeStamp type
// (src/Database/Migration/Type/TimeStamp.php) is wired into Doctrine and
// produces a TIMESTAMP column on MySQL/MariaDB. The migration that exercises
// it is tests/e2e/app/Database/migrations/2026-05-08_TimeStampType.php; here
// we query INFORMATION_SCHEMA via a probe to confirm the result.

describe("Migration System - Custom TimeStamp type", () => {
    before(() => {
        cy.dbSeed();
    });

    it("creates a TIMESTAMP column on the seeded test table", () => {
        cy.request("/migration/checkTimestampType").then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body.found, "z_test_timestamp_type.created exists").to.eq(true);
            expect(res.body.dataType.toLowerCase()).to.eq("timestamp");
            expect(res.body.columnType.toLowerCase()).to.eq("timestamp");
        });
    });
});
