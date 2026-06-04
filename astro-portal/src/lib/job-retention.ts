import { articleList, jobList } from './portal';
import { jobIsPubliclyVisible } from './public-content';

type JobRef = {
  slug: string;
  city: { slug: string; name: string };
  category?: { slug: string; name: string } | null;
};

function filterRelated<T extends { slug: string }>(items: T[], currentSlug: string): T[] {
  return items.filter((j) => j.slug !== currentSlug && j.slug.trim());
}

export async function loadJobRetentionRelated(job: JobRef) {
  const [relatedCity, relatedCategory, blogSafety, blogTips] = await Promise.all([
    jobList({ city: job.city.slug, perPage: 4, page: 1 }),
    job.category ? jobList({ category: job.category.slug, perPage: 4, page: 1 }) : Promise.resolve({ jobs: [] }),
    articleList({ category: 'seguranca-para-candidatos', perPage: 4, activeOnly: true }),
    articleList({ category: 'dicas-para-candidatura', perPage: 4, activeOnly: true }),
  ]);

  const relatedCityJobs = filterRelated(relatedCity.jobs, job.slug)
    .filter((j) => jobIsPubliclyVisible(j))
    .slice(0, 3);
  const relatedCategoryJobs = filterRelated(relatedCategory.jobs, job.slug)
    .filter((j) => jobIsPubliclyVisible(j))
    .slice(0, 3);

  const blogRelatedMap = new Map<string, (typeof blogSafety.articles)[number]>();
  for (const post of [...blogSafety.articles, ...blogTips.articles]) {
    if (!blogRelatedMap.has(post.slug)) blogRelatedMap.set(post.slug, post);
  }
  const blogRelated = [...blogRelatedMap.values()].slice(0, 4);

  return { relatedCityJobs, relatedCategoryJobs, blogRelated };
}
