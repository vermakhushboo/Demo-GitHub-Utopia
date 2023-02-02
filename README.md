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

## Steps to set up ngrok
- Open a terminal window and navigate to the directory where you have installed ngrok.
- Start ngrok by running the command `ngrok http [port number]`, where the "port number" is the local port on which your server is running. For example, if your server is running on port 3000, the command would be `ngrok http 3000`.
- Ngrok will provide you with a public URL that you can use to receive webhook events. This URL will be in the format `https://[randomstring].ngrok.io`
In your app or service that is sending the webhooks, configure the webhook URL to be the URL provided by ngrok, which should be in the format `https://[randomstring].ngrok.io/webhook`
- Once you have done this, any events sent to that URL will be forwarded to your local server and handled accordingly.
> Note: The randomstring is unique for each ngrok session, so you will need to update the webhook url if you restart ngrok or if the connection is lost.
