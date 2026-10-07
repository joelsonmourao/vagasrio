import assert from 'node:assert/strict';
import {
  blogPostCanBeIndexed,
  BULK_EDITORIAL_PREFIX,
  requiresEditorialReview,
} from '../src/lib/blog-indexing';

assert.equal(BULK_EDITORIAL_PREFIX, 'rj-local-');
assert.equal(requiresEditorialReview('rj-local-001-curriculo'), true);
assert.equal(requiresEditorialReview('RJ-LOCAL-205-entrevista'), true);
assert.equal(requiresEditorialReview('guia-original-revisado'), false);
assert.equal(requiresEditorialReview(undefined), false);

assert.equal(blogPostCanBeIndexed({ slug: 'rj-local-001-curriculo', isIndexable: true }, true), false);
assert.equal(blogPostCanBeIndexed({ slug: 'guia-original-revisado', isIndexable: true }, true), true);
assert.equal(blogPostCanBeIndexed({ slug: 'guia-original-revisado', isIndexable: false }, true), false);
assert.equal(blogPostCanBeIndexed({ slug: 'guia-original-revisado', isIndexable: true }, false), false);

console.log('blog-indexing: ok');
