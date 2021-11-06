default: help

build:
	@docker build -t record.rat.la .

up:
	@docker run -d --rm -p 8081:80 --name my-record.rat.la record.rat.la
	@echo "Running my-record.rat.la in http://localhost:8081"

down:
	@docker stop my-record.rat.la

clean:
	@docker stop my-record.rat.la 2> /dev/null
	@docker rmi record.rat.la

help:
	@echo "make build"
	@echo "make up"
	@echo "make down"
	@echo "make clean"
