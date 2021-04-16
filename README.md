# Opening Hours for Charging Stations:

Opening Hours for Charging Stations

# Prerequisites

You have to have `docker` and `docker-composer` installed on your system in order to run this project.

---
# Up and Running
1. clone the repository
    - `git clone git@github.com:rasadeghnasab/opening_hours.git`

2. cd to the repository directory
    - `cd opening_hours`
    
3. up and run the whole project
    - `make project`

4. you can have access to the APIs using these routes OR you can just download the Postman and call them
    - 

---
# A quick note for the reviewers

- I wrote test functions name in a snake_case type to be more readable despite that breaks the PSR-12 rules.

---
# Useful make commands

Here we listed some useful commands that you can use to develop the application:

### Docker commands
- Run all the necessary containers to make project work and accessible through a URI and PORT
    - `make up`
    
- Stop all the containers and free resources and ports
    - `make down`

### Back-end commands

- Install laravel composer packages and create `.env` file from the `.env.example`
    - `make laravel-dep`

- Run all the tests
    - `make test`
    
- You can run a specific test by passing the `filter` argument to the command
    - `make test filter=TEST_FILE_OR_FUNCTION`
