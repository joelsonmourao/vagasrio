FROM node:22-alpine AS build

WORKDIR /app

RUN apk add --no-cache openssl libc6-compat

COPY package*.json ./

RUN npm ci --ignore-scripts

COPY . .

ARG SITE_BASE_URL
ARG DATABASE_URL

ENV SITE_BASE_URL=$SITE_BASE_URL
ENV DATABASE_URL=$DATABASE_URL
ENV PRISMA_PROVIDER=postgres

RUN npm run build:postgres


FROM node:22-alpine AS runtime

WORKDIR /app

RUN apk add --no-cache openssl libc6-compat

ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=4321
ENV PRISMA_PROVIDER=postgres

COPY --from=build /app/package*.json ./
COPY --from=build /app/node_modules ./node_modules
COPY --from=build /app/dist ./dist
COPY --from=build /app/public ./public
COPY --from=build /app/prisma ./prisma
COPY --from=build /app/scripts ./scripts

EXPOSE 4321

CMD ["node", "./dist/server/entry.mjs"]