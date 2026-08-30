# Third-Party Notices

## Upstream Reference

Project: Second Brain Obsidian plugin  
Repository: https://github.com/jmoraispk/2nd-brain-plugin  
Author: João Morais  
Inspected commit: `e5efd69`

The upstream `package.json` declares license `MIT`. A root `LICENSE` file was not present in the inspected clone, so direct code reuse should be conservative until license text/provenance is confirmed.

The LifeWheel SaaS architecture may adapt concepts from the upstream project, including:

- Wheel of Life area modeling
- daily/weekly/monthly/quarterly/yearly review workflows
- habits, goals, projects, lessons, and Ask-your-life concepts
- selective AI retrieval
- model routing and cost awareness

No upstream image asset should be copied into this project unless its provenance and reuse rights are confirmed.

## Upstream Dependencies Observed

From upstream `package.json`:

- TypeScript
- esbuild
- obsidian
- tslib
- builtin-modules
- @types/node
- @vapi-ai/web

These dependencies are part of the upstream Obsidian plugin and are not automatically part of the planned Laravel SaaS.

## Planned Platform Dependencies

The target platform will use Laravel/PHP dependencies. A complete dependency license report must be generated during implementation and release packaging phases.
