# Brightcove Video Connect End to End Tests

Brightcove e2e tests use [Cypress](https://www.cypress.io/), [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), and require a valid Brightcove Studio account.

## Requirements

* docker
* docker-compose
* npm (>= 12)
* Brghtcove account

## Instructions

### Start

* Start the local environment (WP env and Elasticsearch containers): `npm run env:start`
* Install all node packages: `npm i`
* Build assets: `npm run build`
* Initial database setup: `npm run cypress:setup`

### Running Test suite

Brightcove e2e needs valid account details to perform the tests, there's two possible ways to go about it in order to run the tests:

### Defining environmental variables in CLI:

* Set the variables in CLI and run cypress: `export BRIGHTCOVE_ACCOUNT_ID=your_var && export BRIGHTCOVE_CLIENT_ID=your_var && export BRIGHTCOVE_CLIENT_SECRET=your_var && npm run cypress:start`

### Define in config file:
press:
* Use an alternative config method and define the following cypress variables: `brightcoveAccountId`, `brightcoveClientId` and `brightcoveClientSecret`. See: https://docs.cypress.io/guides/guides/environment-variables
* Run cypress: `npm run cypress:open`
