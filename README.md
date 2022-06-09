### Iona PHP Developer Test Exercise - Cat + Dog Browser API

Test selected was the PHP Laravel PDF file

## Installation instructions:

1. Clone repository
2. Open root directory in a terminal (where the Dockerfile is located)
3. Run command:

```bash
docker-compose build && docker-compose up
```

4. Open [http://localhost:8088/](http://localhost:8088), you should see a Laravel welcome screen if the application is up and running.

## Routes

All api routes require the `page` and `limit` parameter as instructed, except for the /v1/:image route.

`GET /v1/breeds` - Returns a combined list of cat and dog breeds
`GET /v1/breeds/:breed` - Returns images of specified cat or dog breed
`GET /v1/list` - Returns a combined list of images for cats and dogs
`GET /v1/:image` - Returns an image of a cat or dog according to the image ID provided

## Notes

- Please change the ports in the docker-compose.yml if the one written there is already used in your local machine. PHP is using port 9000 and Nginx is using 8088.
- I understand that the .env file should never be committed to the repository, but for easier installation of this test exercise I have ommitted the file in the .gitignore file
