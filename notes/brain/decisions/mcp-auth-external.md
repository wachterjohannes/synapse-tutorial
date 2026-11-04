---
type: Decision
title: "Protect the MCP server with an external OAuth server"
description: "Why the brain's MCP server should not run its own login."
tags: [mcp, oauth, security]
status: stable
generated:
  by: human:johannes
  at: 2026-10-27T08:00:00Z
---

# Protect the MCP server with an external OAuth server

## Context

The brain gets an MCP server. It needs a login, and users and clients already exist in an OAuth server.

## Decision

The MCP server does not run its own login. It validates tokens issued by an external OAuth server and reads their scopes.

## Consequences

* Token lifetime and revocation stay in one place.
* The MCP server only has to check a token.
* Open point: how the league OAuth2 server fits the MCP flow needs a look at its issue tracker. The Sulu MCP bundle solves authentication itself and is the reference to compare with.
