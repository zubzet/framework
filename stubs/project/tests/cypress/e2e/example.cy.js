describe('Example', () => {

    it('answers the example route', () => {
        cy.visit('/hello/world');
        cy.contains('Hello world!');
    });

});
