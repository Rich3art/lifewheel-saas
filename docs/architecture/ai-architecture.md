# AI Architecture

## Core AI Framework

Core owns provider settings and usage control. Product AI features are plugins.

Core responsibilities:

- provider registry
- encrypted credentials
- model registry
- model routing
- usage metering
- package limits
- cost metadata
- request/response metadata
- test connection tooling

Initial providers:

- OpenAI
- Anthropic

## AI Product Plugins

AI features should be implemented as plugins:

- AI Life Analysis
- AI Coach / Ask My Life
- AI Reviews
- AI Goal Designer
- AI Habit Designer

These plugins call the core AI service abstraction.

## Selective Retrieval

Ask My Life must not dump the user's full database into every prompt.

Use a two-step approach:

1. Build a bounded private data map for the authenticated user.
2. Select only relevant records.
3. Run the final answer with citations/references to selected records.

## Usage Limits

Usage must be server-side.

Possible limits:

- analyses per month
- coach messages per month
- reviews per month
- lesson generations per month
- model access
- estimated token/cost budget

Never trust client-reported usage.

## Privacy Rules

- Provider API keys stay server-side.
- User ownership checks happen before retrieval.
- AI requests include minimum necessary context.
- Cross-user context leakage is a critical security failure.
- Do not log raw secrets or sensitive provider tokens.
- Avoid causal claims from correlation unless evidence supports them.
