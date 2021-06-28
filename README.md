[![Build Status](https://travis-ci.org/LibreCodeCoop/ecommerce-data-colector.svg?branch=master)](https://travis-ci.org/LibreCodeCoop/ecommerce-data-colector)
[![Coverage Status](https://coveralls.io/repos/github/LibreCodeCoop/ecommerce-data-colector/badge.svg?branch=master)](https://coveralls.io/github/LibreCodeCoop/ecommerce-data-colector?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-blue.svg)](https://php.net/)

# Coleta dados de e-commerce

Coleta de de dados em site de e-commerce

## Docker

A execução do projeto com Docker é bem simples:

```bash
git clone https://github.com/librecodecoop/ecommerce-data-colector coleta-dados
cd coleta-dados
cp .env.develop .env
docker-compose up -d
```

exemplo:

```bash
docker-compose exec php7 bin/coleta-dados.php coleta --help
```

O comando `coleta` é o padrão, então é opcional informar o comando `coleta`

```
Options:
  -u, --url=URL                        Base URL do site
  -l, --lojas[=LOJAS]                  Lista de lojas (multiple values allowed)
  -d, --departamentos[=DEPARTAMENTOS]  Lista de departamentos (multiple values allowed)
```

Ou utilize lojas ou utilize departamentos.

## Fluxo a partir de lojas

Não está 100% funcional

## Fluxo a partir de departamentos

Exemplo

```bash
docker-compose exec php7 bin/coleta-dados.php --url=http://teste --departamentos=123 --departamentos=456
```

Onde: `123` e `456` são os códigos de departamentos.

## Output

A saída de dados é no banco PostgreSQL. Para acessá-lo utilize o cliente PostgreSQL de sua preferência com as credenciais que estão em seu arquivo `.env`

O modelo do banco de dados pode ser encontrado nos scripts de migration na pasta db ou conferindo diretamente no banco.
