# Privacy Architecture

## Position

LifeWheel SaaS stores sensitive personal reflection data. The product should support privacy workflows aligned with GDPR, UK GDPR, CCPA/CPRA, and UAE PDPL principles, but should not claim legal compliance without legal review.

## Privacy Center

Members should be able to:

- view privacy information
- correct/update profile information
- request data export
- request account/data deletion
- manage consents/preferences where applicable
- view request status

## Data Export

Exports should be portable:

- JSON by default
- CSV where tabular data is useful
- Markdown archive later if approved

Exports must exclude:

- password hashes
- security tokens
- provider secrets
- payment secrets
- other users' private data

Downloads must be authenticated, authorization-checked, and expire.

## Data Deletion

Deletion is a workflow, not an immediate unsafe cascade:

1. request
2. identity confirmation
3. consequence warning
4. optional grace period
5. processing
6. delete eligible data
7. anonymize retained data where justified
8. record completion

Retention exceptions may include financial records, fraud/security records, backups, and legal obligations.

## Legal Content Versioning

Terms and Privacy Policy require versioned publication:

- version number
- publication timestamp
- immutable content snapshot
- current/archived status
- user acceptance timestamp where acceptance is required

Do not overwrite legally significant historical versions.

## Admin Access To Private Content

Super Admin user lists must not casually expose private LifeWheel scores, journals, AI conversations, messages, or lessons.

Access to private content should require an explicit audited support/privacy workflow.
