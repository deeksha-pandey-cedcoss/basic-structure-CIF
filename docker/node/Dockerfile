# OS Installation
FROM node:14.15

# Node js Installation
RUN apt-get update

# Supervisor Installation 
RUN apt-get -y install supervisor && mkdir -p /app/supervisor && mkdir -p /app/supervisor/conf.d
ADD supervisor.conf /app/supervisor.conf  

RUN npm install -g @angular/cli

WORKDIR /app/

EXPOSE 2000
EXPOSE 3000
EXPOSE 4000
EXPOSE 5000

# Starting all process
CMD ["supervisord", "-c", "/app/supervisor.conf"] 
