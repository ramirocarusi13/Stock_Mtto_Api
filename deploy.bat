git pull
docker build . -t api-panol:latest && docker stop api-panol & docker rm api-panol & docker run -d --restart unless-stopped --name api-panol -p 8586:80 api-panol:latest