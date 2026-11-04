<?php

namespace App\Brain;

use Symfony\AI\Platform\Bridge\Cohere\InputType;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\DocumentInterface;
use Symfony\AI\Store\Document\VectorDocumentInterface;
use Symfony\AI\Store\Document\VectorizerInterface;

/**
 * Cohere embeds a stored document and a search query in different modes. The bundle's vectorizer
 * defaults to document mode, so the retriever gets this wrapper, which asks for query mode.
 */
final class QueryVectorizer implements VectorizerInterface
{
    public function __construct(private readonly VectorizerInterface $inner)
    {
    }

    public function vectorize(string|\Stringable|DocumentInterface|array $values, array $options = []): Vector|VectorDocumentInterface|array
    {
        return $this->inner->vectorize($values, ['input_type' => InputType::SearchQuery] + $options);
    }
}
