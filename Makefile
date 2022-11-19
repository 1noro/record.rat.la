SHELL:=/bin/bash

IMAGE=1noro/record.rat.la
target=local
CONTAINER=record.rat.localhost-container

export DOCKER_BUILDKIT=1

all: help

.PHONY: build
build:
	@docker build -f Dockerfile --target $(target) --build-arg VERSION=$(shell git log -n 1 --pretty=format:"%H") -t $(IMAGE):$(target) .

.PHONY: build-all
build-all:
	@docker build -f Dockerfile --target local -t $(IMAGE):local .
	@docker build -f Dockerfile --target sitemapgen -t $(IMAGE):sitemapgen .
	@docker build -f Dockerfile --target prod --build-arg VERSION=$(shell git log -n 1 --pretty=format:"%H") -t $(IMAGE):prod .

.PHONY: up
up:
	@docker run -d --rm -p 8081:80 -v "$(PWD)/public:/var/www/html" --name $(CONTAINER) $(IMAGE):$(target)
	@echo "Running $(CONTAINER) in http://record.rat.localhost:8081"

.PHONY: up-prod
up-prod:
	@docker run -d --rm -p 8081:80 --name $(CONTAINER) $(IMAGE):prod
	@echo "Running $(CONTAINER) in http://record.rat.localhost:8081"

.PHONY: up-sitemapgen
up-sitemapgen: export target=sitemapgen
up-sitemapgen:
	@docker run -d --rm -p 8081:80 --name $(CONTAINER) $(IMAGE):$(target)
	@echo "Running $(CONTAINER) in http://record.rat.localhost:8081"

.PHONY: down
down:
	@docker stop $(CONTAINER) || true

.PHONY: clean
clean:
	@docker stop $(CONTAINER) 2> /dev/null || true
	@docker rmi $(IMAGE):$(target)

.PHONY: logs
logs:
	@docker logs -f $(CONTAINER)

.PHONY: bash
bash:
	@docker exec -u 0 -it $(CONTAINER) sh

.PHONY: bash-normal
bash-normal:
	@docker exec -it $(CONTAINER) sh

.PHONY: set-permissions
set-permissions:
	@chmod -R 755 .
	@find . -type f -exec chmod 644 -- {} +
	# @chmod 774 set-permissions.sh

.PHONY: analyze
analyze:
	docker run --rm -v $(shell pwd)/public:/app -u $(shell id -u):$(shell id -g) ghcr.io/phpstan/phpstan analyse -l 6 .

.PHONY: rss-update
rss-update:
	$(which python) scripts/rss-update.py

.PHONY: help
help:
	@echo "- default target=local (local || prod)"
	@echo "make build"
	@echo "make build-all"
	@echo "make up"
	@echo "make up-prod"
	@echo "make up-sitemapgen"
	@echo "make down"
	@echo "make clean"
	@echo "make logs"
	@echo "make bash"
	@echo "make bash-normal"
	@echo "make analyze"
	@echo "make rss-update"
