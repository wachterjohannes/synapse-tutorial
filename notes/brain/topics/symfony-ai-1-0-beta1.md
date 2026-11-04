---
type: Topic
title: "Symfony AI 1.0.0-BETA1"
description: "What changed in the first 1.0 beta and what it means for upgrades."
tags: [symfony, ai, upgrade]
status: stable
generated:
  by: human:johannes
  at: 2026-10-27T08:00:00Z
---

# Symfony AI 1.0.0-BETA1

The feature set is settled. The backwards compatibility promise starts with 1.0.0 final, not with the beta.

Changes that break existing code:

* `MessageBag` is immutable. Assign the result of `with()`: `$messages = $messages->with(...)`.
* `AgentInterface::call()` returns an `ExecutionInterface` instead of a result.
* The Chat component is removed. Message stores moved to the Agent component.
* `EmbeddableDocumentInterface` is now `DocumentInterface`.
* `InMemoryPlatform` became `MockPlatformFactory`.

Install with `composer require symfony/ai-bundle:^1.0@beta`. The project needs `minimum-stability: beta`.

See also [the hackathon](/projects/symfony-ai-hackathon.md).
