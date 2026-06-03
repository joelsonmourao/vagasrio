import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

try {
  const activeIndexableJobs = await prisma.job.count({
    where: { isActive: true, isIndexable: true },
  });
  const activeJobs = await prisma.job.count({ where: { isActive: true } });
  const posts = await prisma.blogPost.count({
    where: { isActive: true, isIndexable: true },
  });
  const cities = await prisma.city.count();
  const companies = await prisma.company.count();
  let siteSettings = 'ok';
  try {
    await prisma.siteSetting.count();
  } catch (e) {
    siteSettings = e.message;
  }
  console.log(JSON.stringify({ activeIndexableJobs, activeJobs, posts, cities, companies, siteSettings }, null, 2));
} catch (e) {
  console.error('FAIL', e.message);
  process.exit(1);
} finally {
  await prisma.$disconnect();
}
