/// <reference types="cypress" />

describe('Checkout functionality', () => {

	beforeEach(() => {
		cy.clearCookies()
		cy.visit('/product/beanie/')
		cy.get('.cart .single_add_to_cart_button').click()
		cy.visit('/checkout')
	})

	it('displays steps', () => {
		cy.get('.fc-checkout-steps').should('have.length.greaterThan', 0)
	})

	it('displays order summary', () => {
		cy.get('.fc-checkout-order-review__inner').should('have.length', 1)
		cy.get('#fc-checkout-order-review-heading').should('have.text', 'Order summary')
		cy.get('tr.cart_item').should('have.length.greaterThan', 0)
	})

})
