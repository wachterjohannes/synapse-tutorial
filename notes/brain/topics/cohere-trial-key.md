---
type: Topic
title: "Cohere trial key limits"
description: "The trial key is rate limited and not meant for indexing large corpora."
tags: [cohere, api]
status: stable
generated:
  by: human:johannes
  at: 2026-10-27T08:00:00Z
---

# Cohere trial key limits

A trial key allows 100000 tokens per minute. Indexing a large documentation set hits the limit and throws a RateLimitExceededException. Index a small folder, or use a production key.
