# Plano de recuperação de produção — Vagas RJ

## Escopo e bloqueio atual

Este plano cobre somente `vagasrj.rio.br`, aplicação Coolify `h7szbectndume9kaqbs8rzx6`, repositório `joelsonmourao/vagasrio`, branch `main`.

A aplicação usa SQLite em `/var/www/html/database/portal.sqlite` e possui persistência também em `/var/www/html/storage`.

**Estado em 09/10/2026:** deploy e alterações de dados continuam bloqueados. Não há prova de backup restaurável, armazenamento externo, restauração isolada ou rollback completo.

## Backup consistente de SQLite e arquivos

Copiar o arquivo SQLite bruto durante escritas não é prova de consistência. O ponto de recuperação deve reunir:

1. **SQLite:** backup online produzido pelo mecanismo `.backup` ou `VACUUM INTO`, em novo arquivo, sem sobrescrever o banco ativo.
2. **Verificação:** executar `PRAGMA integrity_check` na cópia, nunca como justificativa para alterar o banco de produção.
3. **Storage:** cópia consistente de `/var/www/html/storage`, associada ao mesmo identificador temporal do banco.
4. **Manifesto:** commit, digest da imagem, versão do PHP, extensões, nomes de variáveis (sem valores), domínio e mounts.
5. **Integridade:** SHA-256, tamanho, data/hora UTC e responsável por artefato.
6. **Destino externo cifrado:** separado do VPS/Coolify.

Retenção inicial sugerida: 7 diários, 4 semanais, 12 mensais e um ponto antes de qualquer alteração de produção. A política final depende do RPO e da capacidade disponível.

## Restauração isolada obrigatória

1. Criar diretório e volumes temporários fora dos caminhos de produção.
2. Copiar o backup do SQLite e o storage para esses recursos temporários.
3. Executar `PRAGMA quick_check` e `PRAGMA integrity_check` na cópia restaurada.
4. Comparar contagens e amostras de vagas, artigos, importações, usuários e slugs.
5. Iniciar container temporário sem domínio de produção, sem cron, importadores, e-mails ou escrita administrativa.
6. Validar home, listagens, páginas de vaga e artigo, admin, sitemap, robots, canonical, páginas institucionais e healthcheck.
7. Verificar uma amostra de arquivos do storage e seus vínculos no banco.
8. Registrar duração, falhas, correções e RTO/RPO observados.
9. Remover os recursos temporários somente após preservar as evidências.

## Rollback de código e dados

- Registrar commit e digest da imagem anterior antes de qualquer deploy.
- Para rollback apenas de código, reutilizar uma imagem previamente validada.
- Para rollback de dados, interromper gravações de forma controlada, preservar o arquivo que falhou e conferir se existem escritas posteriores ao backup.
- Não substituir o SQLite ativo por uma cópia antiga sem plano para reconciliar registros novos.
- Reabrir o site primeiro em modo controlado; validar banco, storage e rotas críticas antes de liberar escrita e importadores.

## Evidências necessárias para liberar produção

| Evidência | Resultado exigido |
|---|---|
| Caminhos persistentes | Banco e storage confirmados no container/volume |
| Backup SQLite externo | ID, data, tamanho, SHA-256 e integrity_check válidos |
| Backup de storage | ID, contagem, tamanho e SHA-256 registrados |
| Restauração isolada | Concluída sem usar caminhos de produção |
| Integridade funcional | Vagas, artigos, usuários, slugs e arquivos comparados |
| Teste público | Rotas, SEO, admin e healthcheck aprovados |
| RTO/RPO | Medidos e aceitos |
| Rollback | Imagem anterior e sequência testadas |
| Aprovação | Responsável e data registrados |

## Proibições até a liberação

- Não acionar deploy do Coolify.
- Não executar `scripts/reset-demo-rj.php`, `SEED_FRESH=1`, seeds, resets ou carga demo.
- Não usar `cp` do arquivo SQLite ativo como único método de backup.
- Não sobrescrever `portal.sqlite` nem editar volumes in-place.
- Não expor segredos nos documentos, manifests ou logs.

A existência deste plano não comprova backup e não autoriza deploy. A liberação depende da restauração isolada e das evidências acima.
