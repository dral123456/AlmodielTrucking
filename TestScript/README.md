# TestScript

Playwright end-to-end tests for Almodiel Trucking Services.

## Run from this folder — always

Playwright must be started with the copy installed in `TestScript/node_modules`.
Running `npx playwright test` from the repository root (or any folder without
this `node_modules`) makes npx download its own Playwright, which then loads the
specs with a second copy of the test registry and fails with:

```
Error: Playwright Test did not expect test() to be called here.
Error: No tests found.
```

That is a runner/version mismatch, not a bug in the tests.

```bash
cd TestScript

npm test                       # every spec, every browser project
npm run test:login             # login.spec.js only
npm test -- --project=chromium # one browser
npm run report                 # open the HTML report
```

The app must be running at `http://localhost/AlmodielTrucking/`.
Override the target with `BASE_URL`, for example:

```bash
BASE_URL=http://localhost:8080/almodieltrucking/ npm test
```

## Test accounts

Seeded in `../almodieltrucking.sql`; passwords verified against the stored bcrypt
hashes. Override any of them with `ADMIN_PHONE`, `ADMIN_PASSWORD`,
`DRIVER_PHONE`, `DRIVER_PASSWORD`, `ASSISTANT_PHONE`, `ASSISTANT_PASSWORD`,
`CUSTOMER_PHONE`, `CUSTOMER_PASSWORD`.

| Role | Phone | Password |
|---|---|---|
| admin | 09123456789 | 123456 |
| driver | 09320312321 | 123456 |
| assistant | 09321312321 | 123456 |
| customer | 09123132131 | 123456 |

## Layout

- `tests/login.spec.js` — login for all four user types plus a rejected-password case.
- `tests/example.spec.js` — untouched Playwright starter, still points at playwright.dev.
- `playwright.config.js` — `baseURL` (trailing slash required for relative paths) and
  chromium/firefox/webkit projects.
