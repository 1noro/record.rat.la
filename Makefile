SHELL:=/bin/bash

IMAGE=record.localhost
TAG=latest
CONTAINER=record.localhost-container

all: build up

.PHONY: build
build:
	@docker build -f local.Dockerfile -t $(IMAGE):$(TAG) .

.PHONY: up
up:
	@docker run -d --rm -p 8081:80 -v "$(PWD)/public:/var/www/html" --name $(CONTAINER) $(IMAGE):$(TAG)
	@echo "Running $(CONTAINER) in http://record.localhost:8081"

.PHONY: down
down:
	@docker stop $(CONTAINER)

.PHONY: clean
clean:
	@docker stop $(CONTAINER) 2> /dev/null
	@docker rmi $(IMAGE):$(TAG)

.PHONY: logs
logs:
	@docker logs -f $(CONTAINER)

.PHONY: bash-in
bash-in:
	@docker exec -u 0 -it $(CONTAINER) sh

.PHONY: rss/update
rss/update:
	$(which python) rss-update.py

.PHONY: set-permissions
set-permissions:
	@chmod -R 755 .
	@find . -type f -exec chmod 644 -- {} +
	# @chmod 774 set-permissions.sh

.PHONY: rebuild
rebuild: clean build up

.PHONY: analyze
analyze:
	docker run --rm -v $(shell pwd)/public:/app -u $(shell id -u):$(shell id -g) ghcr.io/phpstan/phpstan analyse -l 8 .

.PHONY: help
help:
	@echo "make build"
	@echo "make up"
	@echo "make down"
	@echo "make clean"
