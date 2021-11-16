IMAGE=record.localhost
TAG=latest
CONTAINER=record.localhost-container

default: help

build:
	@docker build -f local.Dockerfile -t $(IMAGE):$(TAG) .

up:
	@docker run -d --rm -p 8081:80 -v "$(PWD)/public:/var/www/html" --name $(CONTAINER) $(IMAGE):$(TAG)
	@echo "Running $(CONTAINER) in http://record.localhost:8081"

down:
	@docker stop $(CONTAINER)

clean:
	@docker stop $(CONTAINER) 2> /dev/null
	@docker rmi $(IMAGE):$(TAG)

logs:
	@docker logs -f $(CONTAINER)

bash-in:
	@docker exec -u 0 -it $(CONTAINER) sh

help:
	@echo "make build"
	@echo "make up"
	@echo "make down"
	@echo "make clean"
