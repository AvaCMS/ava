# Security Policy

This document outlines how security issues should be reported and how Ava CMS handles vulnerability disclosures. Ava CMS is an open-source project under active development and is not a commercial product.

## Supported Versions

Only the versions listed below are currently eligible for security-related fixes.

| Version | Supported | Notes |
|-------|-----------|-------|
| v1.1.x | ✅ | Actively maintained |
| v1.0.x | ❌ | No longer supported |
| v25.x.x | ❌ | Early experimental CalVer releases prior to v1.0. Please [manually update](https://ava.addy.zone/docs/updating#content-manual-updates). |

Security reports affecting unsupported versions may not be addressed.

---

## Reporting a Vulnerability

If you believe you have found a security vulnerability in Ava CMS, please disclose it **privately**.

**Contact:**  
📧 **ava@addy.zone**

Please do **not** open a public GitHub issue for security-related reports.

When reporting a vulnerability, include as much detail as possible:

- A description of the issue and its potential impact
- Steps to reproduce the issue, if known
- Affected versions or components
- Any relevant logs, screenshots, or proof-of-concept code (if safe to share)

Clear and responsible reports make it much easier to understand and assess the issue.

---

## Responsible Disclosure

Ava CMS follows a **responsible disclosure approach**:

- Please allow reasonable time for the issue to be reviewed before sharing details publicly
- Avoid exploiting the issue beyond what is necessary to demonstrate it
- Do not access or modify data that does not belong to you

Because Ava CMS is maintained by volunteers, response times may vary. Not all reports will result in a fix, particularly if the issue depends on configuration, third-party code, or unsupported versions.

---

## What to Expect

- Reports will be reviewed on a best-effort basis
- Valid issues may be fixed in a future release or documented as known limitations
- There is no guaranteed timeline for responses or patches
- Security fixes may be released without advance notice

Ava CMS does not currently operate a bug bounty program.

---

## Scope & Limitations

The following are generally **out of scope**:

- Vulnerabilities caused by misconfiguration
- Issues in third-party plugins, themes, or dependencies
- Local development environments
- Attacks requiring full server compromise
- Social engineering or phishing attacks

Site owners are responsible for securing their hosting environment, PHP configuration, web server, and deployment practices.

---

## Project Status

Ava CMS is an evolving project. Security-related behaviour may change between versions, and no part of the system should be assumed to be complete, audited, or suitable for high-risk or sensitive environments without independent review.

By using Ava CMS, you acknowledge that it is provided without warranty and that you assume responsibility for evaluating its suitability for your use case.
