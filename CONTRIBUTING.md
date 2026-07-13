# Contributing to wave-client

Thank you for your interest in contributing to wave-client. This package automates the Wave Business dashboard by replaying its internal GraphQL requests. Since Wave does not provide a public REST API for these features, community contributions are essential to keep the API integrations up-to-date and to replace remaining stubs.

---

## Development Setup

To set up your local development environment:

1. Fork and clone the repository:
   ```bash
   git clone https://github.com/your-username/wave-client.git
   cd wave-client
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the test suite to verify everything is working:
   ```bash
   composer test
   ```

---

## How to Intercept and Implement Wave Requests

Most actions in the Wave Business dashboard use a private GraphQL API endpoint. If you want to replace any existing stubs (e.g., balance, payouts, or payment requests) with real implementations:

### 1. Intercept Network Traffic
1. Log in to your Wave Business account at https://business.wave.com.
2. Open your browser's Developer Tools (F12) and navigate to the Network tab.
3. Filter by Fetch/XHR requests.
4. Perform the action you want to implement (such as checking your balance or sending a payout).
5. Locate the POST request targeting `/a/business_graphql` (or the country-specific endpoint).

### 2. Analyze the Query and Payload
* **Payload**: Capture the GraphQL query or mutation structure and the variables sent in the request.
* **Headers**: Identify any custom headers required.
* **Response**: Note the exact JSON structure of the returned data.

### 3. Implement the Action
1. Implement or modify the relevant action class in `src/Actions/`.
2. Add the GraphQL query or mutation as a constant string in the class.
3. Update or create a Data Transfer Object (DTO) in `src/Data/` to map the response JSON.
4. Add corresponding tests in `tests/Unit/` and update the mock class `WaveFake` in `src/Testing/` to support testing.

---

## Coding Standards

We follow standard Laravel coding conventions. Please ensure:
* Your code adheres to PSR-12 coding standards.
* You do not hardcode currencies (use configuration settings or dynamic lookup instead).
* The minimum PHP version compatibility (PHP 8.2+) is preserved.

---

## Submission Process

1. Create a descriptive branch for your changes:
   ```bash
   git checkout -b feature/replace-balance-stub
   ```
2. Commit your changes with clear, concise messages.
3. Push your branch to your fork and submit a Pull Request (PR) to the `main` branch of the parent repository.
4. Provide a clear description in your PR of what was modified, including sample GraphQL payloads (excluding sensitive data) and verification steps.
