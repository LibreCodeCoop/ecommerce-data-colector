# Coleta dados de e-commerce

Coleta de de dados em site de e-commerce

## Docker

Executar a aplicação via Docker é recomendável para quem queira ter um ambiente isolado e controlado sem ter necessidade de realizar alterações no sistema operacional. Esta é a solução mais recomendável para desenvolvedores do projeto.

A execução do projeto com Docker é bem simples:

```bash
git clone https://github.com/lyseontech/coleta-dados
cd coleta-dados
cp .env.develop .env
docker-compose up -d
```
Nos comandos abaixo, onde você lê `coleta-dados.phar` coloque o seguinte
comando:

```bash
docker-compose exec php7 bin/coleta-dados.php
```

exemplo:

```bash
docker-compose exec php7 bin/coleta-dados.php coleta --help
```

## PHAR

Executar a aplicação via `phar` é para usuários finais.

Baixe a versão mais recente do projeto em [releases](https://github.com/LyseonTech/coleta-coleta-dados/releases/latest/download/coleta-dados.phar)

## Notas para desenvolvedores

Para gerar o arquivo `phar` do projeto execute o script `bin/compile`