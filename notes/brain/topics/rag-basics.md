---
type: Topic
title: "RAG basics"
description: "How retrieval augmented generation works in three moves."
tags: [rag, search, embeddings]
status: stable
generated:
  by: human:johannes
  at: 2026-10-27T08:00:00Z
---

# RAG basics

Three moves: retrieve candidates, augment the prompt with them, generate the answer.

* Embeddings turn text into vectors. Similar meaning lands close together.
* Plain vector search finds neighbours, but exact terms like error codes can lose against fuzzy matches. Hybrid search adds keyword matching.
* A reranker sorts the candidates by real relevance. The retrieval order alone is not a ranking.
* Cohere embeds queries and documents in different modes, search_query and search_document.
