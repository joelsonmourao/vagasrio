# Runbook de recuperação — SQLite e armazenamento

> **Estado:** procedimento preparado, ainda não executado nem homologado em produção.
>
> Deploy, migrations, bootstrap, seeds, limpeza e reset permanecem bloqueados até que backup, restauração isolada e rollback sejam comprovados.

Este runbook vale somente para `vagasrj.rio.br`, aplicativo Coolify `h7szbectndume9kaqbs8rzx6`, repositório `joelsonmourao/vagasrio`, branch `main`, banco SQLite e volumes pertencentes a esse aplicativo.

## Escopo proibido

- não executar `scripts/reset-demo-rj.php`;
- não habilitar `APP_ALLOW_BOOTSTRAP_WRITES` em produção;
- não criar, migrar, semear, limpar ou expurgar o SQLite de produção;
- não reiniciar o VPS;
- não acessar bancos ou volumes de outros aplicativos;
- não substituir backup por cópia simples de um SQLite aberto.

## 1. Gate de autorização e inventário

Antes de qualquer leitura de produção, registre em local restrito e sem dados pessoais:

- autorização e responsável;
- UUID, domínio, repo, branch e commit implantado;
- container e imagem em execução;
- origem/mount exatos de `/var/www/html/database`;
- origem/mount exatos de `/var/www/html/storage`;
- caminho real do `portal.sqlite`;
- modo de journal (`DELETE`, `WAL` etc.);
- tamanho do banco e dos volumes;
- espaço livre no host e no destino independente;
- política de retenção;
- plano para bloquear temporariamente novas escritas somente neste app.

Pare se qualquer mount estiver ausente, compartilhado com outro app ou não puder ser identificado com segurança.

## 2. Obter um ponto consistente

Para manter SQLite e arquivos do storage no mesmo ponto operacional:

1. ative manutenção somente no aplicativo Vagas RJ;
2. bloqueie novas importações, uploads e gravações administrativas;
3. aguarde requisições de escrita terminarem;
4. confirme que nenhum processo deste app mantém transação de escrita;
5. gere o backup online do SQLite;
6. archive o volume de storage;
7. calcule checksums e copie os artefatos ao destino independente;
8. só então retire a manutenção.

Não pare ou reinicie outros serviços.

## 3. Diretório de trabalho protegido

Use caminho dedicado fora do repositório e fora dos volumes de produção.

```bash
set -euo pipefail
umask 077

export BACKUP_ID="$(date -u +%Y%m%dT%H%M%SZ)"
export BACKUP_WORK_DIR="/caminho/seguro/vagas-rj-${BACKUP_ID}"
mkdir -p "${BACKUP_WORK_DIR}"
```

Defina caminhos explícitos. Não use `/`, `~`, curingas ou variáveis não validadas como origem/destino.

## 4. Backup consistente do SQLite

A API online `.backup` do SQLite cria uma imagem consistente mesmo quando o banco usa WAL. Não copie apenas `portal.sqlite` com `cp` enquanto o app estiver ativo.

```bash
export SOURCE_DB="/mount/exato/database/portal.sqlite"
export BACKUP_DB="${BACKUP_WORK_DIR}/portal.sqlite"

test -f "${SOURCE_DB}"
test "${SOURCE_DB}" != "/"
test ! -L "${SOURCE_DB}"

sqlite3 "${SOURCE_DB}" ".timeout 5000" ".backup '${BACKUP_DB}'"

sqlite3 "${BACKUP_DB}" "PRAGMA quick_check;"
sqlite3 "${BACKUP_DB}" "PRAGMA foreign_key_check;"
sha256sum "${BACKUP_DB}" > "${BACKUP_WORK_DIR}/portal.sqlite.sha256"
```

Critérios:

- `quick_check` deve retornar apenas `ok`;
- `foreign_key_check` deve retornar vazio;
- o arquivo deve ter tamanho maior que zero;
- qualquer erro invalida a execução.

## 5. Backup do volume de armazenamento

Valide que o mount corresponde exclusivamente a `/var/www/html/storage`.

```bash
export SOURCE_STORAGE="/mount/exato/storage"
export STORAGE_ARCHIVE="${BACKUP_WORK_DIR}/storage.tar.gz"

test -d "${SOURCE_STORAGE}"
test "${SOURCE_STORAGE}" != "/"
test "${SOURCE_STORAGE}" != "/home"

tar   --one-file-system   --xattrs   --acls   --numeric-owner   -czf "${STORAGE_ARCHIVE}"   -C "${SOURCE_STORAGE}" .

sha256sum "${STORAGE_ARCHIVE}" > "${BACKUP_WORK_DIR}/storage.tar.gz.sha256"
```

O backup precisa incluir imports, logs operacionais necessários e uploads persistentes conforme o inventário. Não inclua arquivos temporários externos ao volume nem dados de outros apps.

## 6. Manifesto e destino independente

Registre sem segredos:

```text
backup_id=<UTC>
application_uuid=h7szbectndume9kaqbs8rzx6
domain=vagasrj.rio.br
repository=joelsonmourao/vagasrio
branch=main
deployed_commit=<sha>
php_version=<major.minor>
sqlite_version=<major.minor.patch>
journal_mode=<modo>
online_backup_completed=true
quick_check_passed=true
foreign_key_check_passed=true
database_sha256=<sha256>
storage_sha256=<sha256>
independent_destination=<identificador sem credenciais>
retention_until=<data UTC>
write_pause_started_at=<UTC>
write_pause_finished_at=<UTC>
```

Copie banco, storage, checksums e manifesto para destino criptografado e independente do VPS/volumes. Recalcule checksums no destino. Uma cópia apenas no mesmo VPS não libera o gate.

## 7. Restauração isolada

Crie ambiente descartável:

- sem domínio público;
- sem acesso aos volumes de produção;
- volumes novos e vazios;
- `APP_ENV=test`;
- `APP_ALLOW_BOOTSTRAP_WRITES=false`;
- e-mail, analytics, anúncios, indexação e integrações externas desabilitados;
- commit idêntico ao manifesto;
- rede de saída restrita.

Transfira os artefatos a partir do destino independente e verifique os checksums.

```bash
sha256sum --check portal.sqlite.sha256
sha256sum --check storage.tar.gz.sha256

export RESTORE_DATABASE_DIR="/ambiente-descartavel/database"
export RESTORE_STORAGE_DIR="/ambiente-descartavel/storage"

test "${RESTORE_DATABASE_DIR}" != "/"
test "${RESTORE_STORAGE_DIR}" != "/"

mkdir -p "${RESTORE_DATABASE_DIR}" "${RESTORE_STORAGE_DIR}"
install -m 0600 portal.sqlite "${RESTORE_DATABASE_DIR}/portal.sqlite"
tar -xzf storage.tar.gz -C "${RESTORE_STORAGE_DIR}"

sqlite3 "${RESTORE_DATABASE_DIR}/portal.sqlite" "PRAGMA quick_check;"
sqlite3 "${RESTORE_DATABASE_DIR}/portal.sqlite" "PRAGMA foreign_key_check;"
```

Os destinos devem ser exclusivos do ambiente descartável. Se houver dúvida, pare antes de `mkdir`, `install` ou `tar`.

## 8. Verificação funcional sem expor dados

Registre apenas contagens e resultados agregados.

Critérios mínimos:

- integridade e chaves estrangeiras aprovadas;
- schema e índices esperados presentes;
- contagens agregadas compatíveis com o manifesto;
- aplicação inicia com bootstrap de escrita bloqueado;
- home, vagas, blog, sitemap, robots e admin/login respondem;
- uma vaga e um artigo existentes podem ser lidos sem alteração;
- arquivos referenciados no banco estão presentes no storage restaurado;
- importação e reset permanecem desabilitados;
- anúncios, e-mail, indexação e integrações externas não disparam;
- nenhum conteúdo demo é criado.

Exemplo agregado:

```sql
SELECT name
FROM sqlite_schema
WHERE type = 'table'
ORDER BY name;

PRAGMA quick_check;
PRAGMA foreign_key_check;
```

Não publique títulos, contatos, candidaturas, hashes, credenciais ou conteúdo privado nas evidências.

## 9. Ensaio de rollback

No ambiente isolado:

1. registre commit e checksums do manifesto;
2. implante o candidato sobre os volumes descartáveis;
3. execute smoke tests somente de leitura;
4. volte ao commit/imagem anterior;
5. apague somente os volumes descartáveis, após validar os caminhos;
6. recrie volumes isolados vazios;
7. restaure novamente banco e storage;
8. suba a imagem anterior com bootstrap de escrita bloqueado;
9. repita integridade, rotas e amostras de leitura;
10. documente duração, operador e resultado.

Reverter código sem restaurar e validar SQLite/storage não comprova rollback.

## 10. Critérios para liberar o deploy

- [ ] UUID, domínio, repo, branch, commit e mounts conferidos;
- [ ] backup SQLite via API online concluído;
- [ ] `quick_check` e `foreign_key_check` aprovados;
- [ ] storage do mesmo ponto operacional arquivado;
- [ ] checksums verificados no destino independente;
- [ ] retenção e acesso documentados;
- [ ] restauração isolada concluída;
- [ ] aplicação e rotas críticas aprovadas no ambiente isolado;
- [ ] rollback de código, SQLite e storage ensaiado;
- [ ] evidência não sensível anexada ao checklist oficial;
- [ ] autorização explícita para a janela de deploy.

Até todos os itens passarem, mantenha o PR sem merge/deploy e não execute bootstrap, migrations, seeds, limpeza ou reset.

## 11. Retenção e evidência

Política inicial sugerida, sujeita à aprovação operacional:

- diário: 14 cópias;
- semanal: 8 cópias;
- mensal: 6 cópias.

Registre ID, horário UTC, operador, commit, versões PHP/SQLite, journal mode, tamanhos, checksums, destino independente, resultado da restauração, testes e rollback.

Nunca anexe o banco, storage, segredos, hashes de senha ou dados pessoais a issues, PRs ou logs públicos.

## 12. Manifesto verificável antes de abrir o gate

O arquivo `scripts/validate-recovery-evidence.php` valida somente metadados e não abre o SQLite, não lê volumes e não chama rede. Ele exige identidade exata do aplicativo, backup online concluído, `quick_check` e `foreign_key_check` aprovados durante a captura, horário da captura dentro da pausa de escrita, hashes e tamanhos dos artefatos, destino independente, restauração isolada, teste de rollback, retenção e decisão explícita do gate. Chaves e padrões de segredo são recusados.

Crie o manifesto fora do repositório e sem credenciais. Valide-o com:

    php scripts/validate-recovery-evidence.php /caminho/restrito/recovery-evidence.json

A mensagem `Recovery evidence: VALID` confirma apenas que o manifesto está completo e coerente. Ela não prova que os comandos foram realmente executados. A evidência original — logs, checksums recalculados no destino independente e resultado da restauração isolada — deve permanecer vinculada ao registro operacional restrito.

O gate só pode mudar para `open` após revisão humana das evidências. Não versionar o manifesto real, artefatos, caminhos internos, credenciais nem dados do banco.
