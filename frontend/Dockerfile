FROM node:lts-alpine
ENV NODE_PATH /app/node_modules/
WORKDIR /app
COPY ./angular_src/package.json ./
RUN npm install -g @angular/cli
RUN npm install
# BEGIN Libraries and dependences below
# END Libraries and dependences
EXPOSE 4200
CMD ["ng", "serve", "--host", "0.0.0.0", "--poll"]