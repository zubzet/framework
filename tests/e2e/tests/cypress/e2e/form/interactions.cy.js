describe("Form Interactions", () => {
    before(() => {
        cy.dbSeed();
    });

    it("Exposes Z on the window object", () => {
        cy.visit("/Form/interactions");

        cy.window().then((win) => {
            expect(win.Z).to.exist;
            expect(win.Z.Forms).to.exist;
        });
    });

    it("Submits the form on a single click after typing", () => {
        cy.intercept("POST", "/Form/interactions").as("submit");
        cy.visit("/Form/interactions");

        cy.form("field_a").type("Neuer Test");
        cy.query("form").find("button").click();

        cy.wait("@submit");
        cy.query("form").contains("Saved!");
        cy.get("@submit.all").should("have.length", 1);
    });

    it("Debounces a rapid double-click submit so only one POST is sent", () => {
        cy.intercept("POST", "/Form/interactions").as("submit");
        cy.visit("/Form/interactions");

        cy.form("field_a").type("Neuer Test");
        cy.query("form").find("button").click().click();

        cy.wait("@submit");
        cy.query("form").contains("Saved!");
        cy.get("@submit.all").should("have.length", 1);
    });

    it("Initializes a field to its configured default value", () => {
        cy.visit("/Form/interactions");

        cy.validateForm({
            field_a: "",
            field_default: "DefaultValue",
            field_select_default: "two",
        });
    });

    it("Resets a select to its configured default value", () => {
        cy.visit("/Form/interactions");

        cy.form("field_select_default").select("three");
        cy.validateForm({
            field_select_default: "three",
        });

        cy.query("reset_form").click();

        cy.validateForm({
            field_select_default: "two",
        });
    });

    it("Resets all field values via form.reset() called on the window form", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_a: "ValueA",
            field_b: "ValueB",
            field_c: "ValueC",
            field_select: "two",
        });
        cy.form("field_default").clear().type("Override");

        cy.window().then((win) => {
            win.form.reset();
        });

        cy.validateForm({
            field_a: "",
            field_b: "",
            field_c: "",
            field_select: "",
            field_default: "DefaultValue",
        });
    });

    it("Resets all field values when the reset button calls form.reset()", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_a: "ValueA",
            field_b: "ValueB",
            field_c: "ValueC",
            field_select: "two",
        });

        cy.query("reset_form").click();

        cy.validateForm({
            field_a: "",
            field_b: "",
            field_c: "",
            field_select: "",
            field_default: "DefaultValue",
        });
    });

    it("Resets only the targeted field when field.reset() is called", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_a: "ValueA",
            field_b: "ValueB",
            field_c: "ValueC",
            field_select: "two",
        });

        cy.query("reset_field_b").click();

        cy.validateForm({
            field_a: "ValueA",
            field_b: "",
            field_c: "ValueC",
            field_select: "two",
        });
    });

    it("Keeps select options after reset", () => {
        cy.visit("/Form/interactions");

        cy.form("field_select").find("option").should("have.length", 4);
        cy.form("field_select").select("two");

        cy.query("reset_form").click();

        cy.form("field_select").find("option").should("have.length", 4);
        cy.form("field_select").find("option").eq(1).should("have.value", "one");
        cy.form("field_select").find("option").eq(2).should("have.value", "two");
        cy.form("field_select").find("option").eq(3).should("have.value", "three");
    });

    it("Resets a field to its configured default value", () => {
        cy.visit("/Form/interactions");

        cy.form("field_default").clear().type("Override").blur();
        cy.validateForm({
            field_default: "Override",
        });

        cy.query("reset_form").click();

        cy.validateForm({
            field_default: "DefaultValue",
        });
    });

    it("Adds options to a select via feedData after the form is filled", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_a: "ValueA",
            field_select: "two",
        });

        cy.window().then((win) => {
            win.form.fields.field_select.feedData([
                { value: "four", text: "Four" },
                { value: "five", text: "Five" },
            ], false);
        });

        cy.form("field_select").find("option").should("have.length", 6);
        cy.form("field_select").find("option").eq(4).should("have.value", "four");
        cy.form("field_select").find("option").eq(5).should("have.value", "five");

        cy.validateForm({
            field_a: "ValueA",
            field_select: "two",
        });

        cy.form("field_select").select("five");
        cy.validateForm({
            field_select: "five",
        });
    });

    it("Removes all options from a select via feedData with an empty array", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_select: "two",
        });

        cy.window().then((win) => {
            win.form.fields.field_select.feedData([]);
        });

        cy.form("field_select").find("option").should("have.length", 1);
        cy.form("field_select").find("option").eq(0).should("have.value", "");
        cy.validateForm({
            field_select: "",
        });
    });

    it("Form can be filled and submitted after reset", () => {
        cy.visit("/Form/interactions");

        cy.fillForm({
            field_a: "Initial",
            field_b: "Initial",
            field_c: "Initial",
            field_select: "one",
        });

        cy.query("reset_form").click();

        cy.fillForm({
            field_a: "ValueA",
            field_b: "ValueB",
            field_c: "ValueC",
            field_select: "three",
        });

        cy.query("form").find("button").click();
        cy.query("form").contains("Saved!");
    });

});
