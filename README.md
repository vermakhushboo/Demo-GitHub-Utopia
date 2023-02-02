# Demo of utopia-php/swoole with GitHub Apps

This project uses the [Utopia framework](https://github.com/utopia-php/framework) and [Swoole](https://github.com/utopia-php/swoole) to build a very simple API which captures GitHub App webhook events using a service like ngrok or smee, demonstrating how to define routes and start the server, both found in the server entrypoint `./app/server.php`.

## Run
```bash
Create .env file and add environment variables
cd 'folder'
docker-compose up -d
```

## Use
The docker-compose config will mount the root folder to `/app` in the container, so you can actively develop your code. To apply changes, restart the stack with:

  `docker-compose restart`

## Resources

- [utopia-php/framework](https://github.com/utopia-php/framework)
- [utopia-php/swoole](https://github.com/utopia-php/swoole)
- [utopia-php/cli](https://github.com/utopia-php/cli)
- [GitHub App](https://docs.github.com/en/github-ae@latest/developers/apps/getting-started-with-apps/setting-up-your-development-environment-to-create-a-github-app#step-5-review-the-github-app-template-code)
