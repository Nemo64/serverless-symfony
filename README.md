Serverless Symfony demo app
===========================
This is a demo project to show, test and develop serverless configuration for symfony.
It is supposed to be the holy grail of a serverless multipage application.
But the serverlessness is completly optional and the same instance can just as well be hosted 
in a completely classical hosting environment if you want.

I usually can't make my work projects public, but I can extract the knowledge from them and demonstrate it here.

I probably continue to tweak this project as my knowledge grows.
One main goal is to not modify or create too many skeleton files to keep it simple.

What features are configured for aws?
-------------------------------------
- Volatile caches write into `/tmp` in the lambda environment
- Logs are written into the lambdas CloudWatch log group
- Static assets (although currently just a robots.txt) are distributed under the same domain as the application behind a CloudFront CDN
- Sessions are persisted in a DynamoDB
- Mails are send using SES (although i haven't added a way to test it.)
- The main Database is an Aurora Serverless which can be securely shared between multiple instances/stages of this application
  and is connected via the rds-data http api to avoid VPC's and also to get pool management.
- A Boilerplate on how to configure scheduled enabling/disabling auf Aurora's pausing feature to cover eg. work times.
- Handling of a paused aurora in the form of a 503 response with a Retry-After. You still need to implement a nive error page though.
 
Documentation
-------------
I build this repo to have working examples of various technics of hosting symfony on aws.
Here are the blog posts currently contained in here:

- [Configure symfony for a serverless lambda environment in bref](https://www.marco.zone/configure-symfony-for-serverless-lambda)
- [Static resource distribution on a aws serverless multipage application](https://www.marco.zone/asset-distribution-on-a-aws-serverless-multipage-application)

There is more information contained in here that I haven't documented yet.

Run it locally
--------------
This project does not provide a full local environment. However, you can use the webserver build into symfony.

- Install dependencies using `composer install`
- Start a database eg using `docker run --rm -p 3306:3306 -e MYSQL_DATABASE=db_name -e MYSQL_USER=db_user -e MYSQL_PASSWORD=db_password -e MYSQL_RANDOM_ROOT_PASSWORD=1 mysql:5.7`.
  This will block and if you end the command, the database will be gone. Of course, you are free to use an existing database server.
- Run `symfony serve` to start the local webserver

Deploy it to AWS
----------------
You need an AWS account and have credentials set up so that the serverless framework can use them.
After that run these commands:

- install dependencies using `composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts` 
- `bin/console cache:clear -e lambda` which will clear and then build (cache) files for the lambda environment.
- `sls deploy -v -c serverless-shared.yml` which will deploy a CloudFormation stack with VPC configuration and a database server (Aurora Serverless).
- `sls deploy -v` which will then deploy this project the infrastructure and code
- `aws s3 sync public "s3://$(sls info -v|grep AssetsBucket:|cut -d ' ' -f2)" --exclude="*.php" --cache-control "public, max-age=300"`
  to upload static files into the assets bucket (I'm still working on automating this)

The deploy command will also output the stack outputs which contains the `DistributionUrl`. Use it to view the page.
If you missed it run `sls info -v` to see all outputs again.

You can also easily deploy the project with a domain by defining the next 2 environment variables:
- `DOMAIN` which must the hostname without anything around it like `example.com`
- `CERTIFICATE` which must be an arn to a certificate for that domain hosted in `us-east-1` (for cloudfront).

What is still missing
---------------------
- I need a more elegant way to deploy assets.
  I have used [serverless-s3-deploy](https://github.com/funkybob/serverless-s3-deploy) in the past,
  but that plugin gets slow when deploying many files, so I want to search/develop a better way.
- Monitoring ~ one of the most important features and I still haven't put time into it. Shame
- Better CDN/proxy handling. The main issue is that `\Symfony\Component\HttpFoundation\Request::getClientIp`
  will return the ip of the CloudFront node, not the real client ip.
  The hostname is working correctly so there is no issue there.
  
How to use this?
----------------
You should probably just create a new symfony project and follow
[Configure symfony for a serverless lambda environment in bref](https://www.marco.zone/configure-symfony-for-serverless-lambda)
while using this repo as a reference.

Contribution
------------
If you want to share your knowledge I'd be happy to accept changes. Feel free to add references to your blog posts if they fit.

The main goal is elegance here. A lot of features can be hacked together, but the more transparent and portable a feature is, the better.
