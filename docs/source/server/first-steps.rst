First steps
===========

To start using the application you need to install PHP 7.4 with extensions listed below.
The dependencies are managed by Composer - it will also validate your environment for required extensions and PHP version.

You can also use a ready-to-use docker container instead of using host installation of PHP, **if you have a possibility always use a docker container**.

**Application requirements:**

- PHP 7.4+ with bcmath, openssl, iconv, ctype, fileinfo, json, pdo, pdo_sqlite, pdo_pgsql, pdo_mysql
- Composer (PHP package manager)
- "file" standard unix shell command
- "sha256sum" unix shell command
- "openssl" unix shell command
- MariaDB 10.2+ / SQLite 3 / PostgreSQL 10.12+
- NodeJS 12.x + NPM (for building simple frontend at installation time)

*Notice: For PostgreSQL configuration please check the configuration reference at :ref:`postgresql_support` page*

Manual installation
===================

At first you need to create your own customized `.env` file with application configuration.
You can create it from a template `.env.dist`.

Make sure the **APP_ENV** is set to **prod**, and that the database connection settings are valid.
On default settings the application should be connecting to a SQLite3 database placed in local file, but please keep in mind, that this is
not optimal for production usage.

.. code:: shell

    cd server
    cp .env.dist .env
    edit .env

To install the application - download dependencies, install database schema use the make task **install**.

.. code:: bash

    make install install_frontend

All right! The application should be ready to go.

Now set up an NGINX + PHP-FPM or Apache to redirect all traffic to point at /public/index.php

For more help please visit: https://symfony.com/doc/current/setup/web_server_configuration.html

When you have the web server up and running, you can check the health check endpoint.

.. code:: bash

    # "test" is defined in "HEALTH_CHECK_CODE" environment variable
    curl http://localhost/health?code=test



Installation with docker
========================

There are at least four choices:

- Use `quay.io/riotkit/file-repository` image by your own and follow the configuration reference
- Generate a docker-compose.yaml using `make print VARIANT="gateway s3 postgres postgres-persistent"` in env_ directory, and create your own environment basing on it
- Copy the env_ environment from this repository and adjust to your needs
- Take a look at our compose in env_ directory and at configuration reference, then create a Kubernetes or other type deployment

Proposed way to choose is the prepared docker-compose environment that is placed in env_ directory.

.. _env: https://github.com/riotkit-org/file-repository/tree/master/env


**Preparing configuration of the environment:**

Before you will run the environment we suggest to take a look at few configuration variables possibly most important
for you at the beginning.


- SECURITY_ADMIN_TOKEN: Will create an admin token for you automatically during container startup
- BASE_URL: Application URL (in web browser)


.. code:: bash

    edit ./env/.env

.. literalinclude:: ../../../env/.env


**Starting the example environment:**

.. code:: bash

    cd ./env
    make up VARIANT="gateway s3 postgres persistent"


**Generating a docker-compose example file:**

.. code:: bash

    cd ./env
    make print VARIANT="gateway s3 postgres persistent"


**Production tips:**

- Use external non-containerized database, do backups. If you want to use containers then use replication
- Do not use SQLite3 for production. Use PostgreSQL or MySQL instead
- Mount data as volumes. Use bind-mounts to have files placed on host filesystem (volumes can be deleted, bind-mounted files stays anyway)
- Use *SECURITY_ADMIN_TOKEN* environment variable to setup an administrative token to be able to log-in into the application
- For automation, use *POST_INSTALL_CMD* to execute console commands to create collections and tokens with ids your applications expects

Development environment setup
=============================

For development purposes use the "dev" configuration, which mounts the application into the docker container,
in effect all changes are present in the application immediately without a rebuild.

You can also run the application with PostgreSQL and/or with S3 as a storage.

.. code:: bash

    cd env
    make up VARIANT="test"

    # with PostgreSQL as a database
    make up VARIANT="dev test postgres"

    # bind application on port 80
    make up VARIANT="dev test postgres gateway"

    # keep all of the changes between environment restarts
    make up VARIANT="dev test postgres postgres-persistent gateway"

    # to have a good, production type configuration
    make up VARIANT="s3 postgres postgres-persistent gateway"

    # to have a production type configuration, that can be behind reverse proxy (do not expose ports itself to host)
    make up VARIANT="s3 postgres postgres-persistent"

    # to have server + Bahub client container and it's test containers
    make up VARIANT="dev test postgres bahub-test"
    make sh@bahub # here you can perform test backups upload/restore


Please check out the detailed instruction in the README_ file.

.. _README: ./env/README.md

Post-installation
=================

At this point you have the application, but you don't have access to it - except if you use docker container and specify the *SECURITY_ADMIN_TOKEN*, then docker container would create an admin token for you.
**You will need to generate an administrative access token (if you dont have one already)**, to be able to create new tokens, manage backups, upload files to storage.
To achieve this goal you need to execute a simple command.

You need to execute **./bin/console auth:generate-admin-token** in the project directory.

So, when you have an administrative token, then you need a second token to upload backups. It's not recommended to use administrative token
on your servers. **Recommended way is to generate a separate token, that is allowed to upload a backup to specified collection**

To do so, check all available roles in the application:

.. code:: bash

    GET /auth/roles?_token=YOUR-ADMIN-TOKEN-HERE

*Note: If you DO NOT KNOW HOW to perform a request, then go to the /api/doc endpoint, type your token and submit a form for given endpoint.*

You should see something like this:

.. code:: json

    {
        "status": true,
        "error_code": null,
        "http_code": 200,
        "errors": [],
        "context": {
            "pagination": {
                "page": 1,
                "perPageLimit": 4096,
                "maxPages": 1
            }
        },
        "message": "Matches found",
        "data": {
            "test-token-full-permissions": "",
            "internal-console-token": "",
            "upload.images": "Allows to upload images",
            "upload.videos": "Allows to upload video files",
            "upload.documents": "Allows to upload documents",
            "upload.backup": "Allows to submit backups",
            "upload.all": "Allows to upload ALL types of files regardless of mime type",
            "upload.enforce_no_password": "Enforce no password for all uploads for this token",
            "upload.enforce_tags_selected_in_token": "Enforce token tags. In result every uploaded file will have tags specified in token regardless if they were sent in request",
            "upload.only_once_successful": "",
            "security.authentication_lookup": "User can check information about ANY token",
            "security.search_for_tokens": "User can browse\/search for tokens",
            "security.overwrite": "User can overwrite files",
            "security.generate_tokens": "User can generate tokens with ANY roles",
            "security.use_technical_endpoints": "User can use technical endpoints to manage the application",
            "security.revoke_tokens": "User can expire other token, so it will be not valid anymore",
            "security.administrator": "Special: Marking - tokens with this marking will not be able to be revoked by non-administrators",
            "security.create_predictable_token_ids": "Allow to specify token id when creating a token",
            "deletion.all_files_including_protected_and_unprotected": "Delete files that do not have a password, and password protected without a password",
            "view.any_file": "Allows to download ANY file, even if a file is password protected",
            "view.files_from_all_tags": "List files from ANY tag that was requested, else the user can list only files by tags allowed in token",
            "view.can_use_listing_endpoint_at_all": "Define that the user can use the listing endpoint (basic usage)",
            "securecopy.stream": "Can use SecureCopy at all?",
            "securecopy.all_secrets_read": "Read SecureCopy secrets: Encryption method, password, initialization vector. With following role can read secrets of any token in the system.",
            "collections.create_new": "Allow person creating a new backup collection",
            "collections.create_new.with_custom_id": "Allow to assign a specific id, when creating a collection",
            "collections.allow_infinite_limits": "Allow creating backup collections that have no limits on size and length",
            "collections.modify_details_of_allowed_collections": "Edit collections where token is added as allowed",
            "collections.modify_any_collection_regardless_if_token_was_allowed_by_collection": "Allow to modify ALL collections. Collection don't have to allow such token which has this role",
            "collections.view_all_collections": "Allow to browse any collection regardless of if the user token was allowed by it or not",
            "collections.can_use_listing_endpoint": "Can use an endpoint that will allow to browse and search collections?",
            "collections.manage_users_in_allowed_collections": "Manage tokens in the collections where our current token is already added as allowed",
            "collections.delete_allowed_collections": "Delete collections where token is added as allowed",
            "collections.upload_to_allowed_collections": "Upload to allowed collections",
            "collections.list_versions_for_allowed_collections": "List versions for collections where the token was added as allowed",
            "collections.delete_versions_for_allowed_collections": "Delete versions only from collections where the token was added as allowed"
        }
    }

To allow only uploading and browsing versions for assigned collections you may choose:

.. code:: bash

    POST /auth/token/generate?_token=YOUR-ADMIN-TOKEN-THERE
    {
        "roles": ["upload.backup", "collections.upload_to_allowed_collections", "collections.list_versions_for_allowed_collections"],
        "data": {
            "tags": [],
            "allowedMimeTypes": [],
            "maxAllowedFileSize": 0
        }
    }

As the response you should get the token id that you need.

**Remember the tokenId**, now you can create collections and grant access for this token to your collections.
Generated token will be able to upload to collections you allow it to.

Check next steps:

1. :ref:`collection_creation`
2. :ref:`granting_access_to_collection`

That's all.
