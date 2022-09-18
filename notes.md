WRT docker, one thing to do is to define production stuff fully in dockerfile and development in docker-compose, keeping it separate from the dockerfile



[16:49] < physikoi> hi #docker. Is there like a best-practice when it comes to using docker-compose for both development and production?
[16:52] < rawtaz> physikoi: hmmm anything in particular you are thinking about?
[16:54] < rawtaz> on a general note i write all my compose files such that they use variables for anything that should be dynamic of configurable. i use e.g.
                  ${SERVICENAME_VARNAME-defaultvaluehere} for those that should be configable but are optional, and i use just ${SERVICENAME_VARNAME} for those that are mandatory
[16:54] < rawtaz> and then put e.g. SERVICENAME_VARNAME=foo in the corresponding .env file
[16:54] < rawtaz> and i always in there set COMPOSE_PROJECT_NAME=bleh as well
[16:55] < rawtaz> so my point is that this way you can reuse the same compose file(s) but you have different configuration in the .env vars depending on which environment you are
                  in
[16:55] < rawtaz> in general, using environment to drive configuration and injecting settings is the way to go
[16:57] < physikoi> rawtaz: yeah. So, I have a pre-existing web-app that I made with a apache+typescript+PHP+sqlite stack. I have semi-working docker-compose setup. That said, I'm
                    stumbling with the fact that development should have a continously running command for front-end development (npm start), and production just needs to
                    successfully run `npm build`. Then there's the issue of my sqlite database. Should that even be
[16:57] < physikoi> a part of the docker image? I mean, how would I deploy without losing that data generated from the prior deployment? ... sorry so confused
[16:58] < physikoi> rawtaz: yes, that seems smart.
[16:59] < rawtaz> youre not the only one confused :P
[16:59] < rawtaz> i'd just start the  npm start  manually inside the image when i run it in dev env
[17:00] < rawtaz> and the sqlite database i'd just bind mount
[17:00] < rawtaz> but like what is our goal, are how do you intend to deploy this in the end?
[17:03] < physikoi> rawtaz: ty. For context, this is all for practice! I want to be able to either upload this to a VPS that has docker running, or to any other computer that I or
                    a friend owns
[17:04] < physikoi> rawtaz: to clarify, do you have two separate docker-compose.yml files for development and for production?
[17:04] < rawtaz> no
[17:04] < rawtaz> same file
[17:04] < physikoi> oh
[17:05] < rawtaz> i think in general, build the Dockerfile (if any, its not always you need one) and compose file such that you can docker-compose up and the stuff runs like you
                  want it in production.
[17:06] < rawtaz> then you use that in your dev env and just replace the .env variables with whatever you use for development db etc. and if you need to start npm then just exec
                  into the running container and execute that manually
[17:07] < physikoi> is that to say, the only difference for you might be that you enter a container and execute "npm start" for development (assuming continuous transpilation of
                    typescript, for example)?
[17:08] < rawtaz> yep, correct
[17:08] < rawtaz> and of course that in the .env i might have another database host, user, etc.
[17:08] < rawtaz> in the variables, assuming i have those
[17:08] < rawtaz> like, WEB_DB_HOST etc
[17:09] < rawtaz> that would be different in my dev env than it is in production
[17:09] < physikoi> hmmmmm
[17:09] < physikoi> ok, i'm beginning to see the light
[17:10] < rawtaz> and it doesnt matter if in my compose file i use an image from docker hub for e.g. the webserver or if i just build:. instead - that only has to do with how the
                  image is built, the setup with a compose file having web, db, mail etc services is the same
[17:11] < physikoi> *thinks*
[17:11] < rawtaz> one thing where you MIGHT want to have different settings is if e.g. in your production your webapp uses an external mail server to send mail, while in your
                  development you want to use e.g. mailhog to "sent" mail from the webapp. you can then e.g. create a docker-compose.override.yaml (or if it's "overrides", see
                  reference) that contains the mailhog service. compose will read both files
[17:14] < physikoi> yeah, i was just wondering what you do if you have different environment variables for production and development. So, docker-compose.override.yaml might be
                    just used to load a different environment file?
[17:15] < rawtaz> no. you use different .env files for that, thats what ive been saying two times or so now :P
[17:15] < rawtaz> your compose files only uses env vars for the stuff you need to be dynamic with, settings and such
[17:15] < rawtaz> then you have those VAR=value in .env file
[17:16] < rawtaz> in your prod env you have the .env file that contains production settings for e.g. database
[17:16] < rawtaz> in your dev env you have another .env file that contians the same vars but with other values
[17:18] < physikoi> rawtaz: i think where i'm slipping is not understanding how you use .env1 for development and .env2 for production. Where is the distinction being made?
[17:19] < rawtaz> there is no distinction except in that they have different values
[17:19] < physikoi> but how are you telling docker-compose which to use?
[17:19] < rawtaz> in your production server you have docker.compose.yaml with a .env file that contians your production settings (VAR=value).
[17:20] < rawtaz> in your development env on your locla machine you have the same docker-compose.yaml file, but possibly with an extra docker-compose.override.yaml file contianing
                  additional services in the same compose file format, and then you have the .env file which is just the same as the one in production but with different values
                  (e.g. another, local, database host or similar)
[17:22] < rawtaz> docker-compose automatically reads any existing .env file and uses it to provide environment variables in the compose file.
[17:23] < physikoi> sorry to be obtuse. So, upon deployment, i need to make sure the correct .env file is installed to the server?
[17:23] < rawtaz> no worries
[17:24] < rawtaz> yes. but thats like you would make sure to configure the server/environment/whatever with environment variables for settings anyway.
[17:24] < rawtaz> its like you (if you werent using environment variables for configuraiton) was instead placing a configuration file there.
[17:25] < rawtaz> neither the .env im talking about or the configuration file you might otherwise use are part of your source code, these are separate from the actual application
                  artifact/package
[17:25] < physikoi> right. *thinks*
[17:28] < rawtaz> consider these two files in your production server: https://kopy.io/y4CHJ
[17:28] < rawtaz> then imagine the same two files on your local machine where you develop the application
[17:29] < rawtaz> ok that was a bad example, let me update it
[17:30] < physikoi> ok. still here fyi
[17:31] < rawtaz> https://kopy.io/9LwZb
[17:31] < rawtaz> take those two files, one copy on your prod server, one on your local dev machine.
[17:31] < physikoi> looking
[17:31] < rawtaz> you'd set the MAIL_SERVER variable's value differently
[17:34] < physikoi> Does deployment include the .env file, or is that ignored?
[17:35] < rawtaz> totally ignored, should not be version controlled
[17:36] < rawtaz> that file is just something that docker-compose reads automatically if it's there. its not uncommon to provide/inject environment variables to a process/service
                  by other means.
[17:36] < rawtaz> and in those cases they sure as heck arent going to be version controlled in the same place as the compose file
[17:38] < physikoi> ohhhh, ok that helps. i was thinking the .env would be included
[17:39] < rawtaz> right. nope nope. then you'd make the mistake so many does, publishing your passwords and other sensitive things on github
[17:39] < rawtaz> so consider the .env file a way to *inject* configuration into your compose solution
[17:39] < physikoi> so, in that case, i might have a .env that's referenced by the app itself. the way the values are interpolated would then depend on the presence of the /.env
                    at top of project (there for development machine, not for production machine)
[17:40] < rawtaz> NOW, once that part is clear, loook at this example. you ahve the app service with the actual webapp yuo made. and you have its database. and in production you
                  set MAIL_SERVER to some external smtp server for sending.
[17:40] < rawtaz> but locally when developing you want to test your mails, see what they look like, e.g. so you want to use e.g. mailhog (google it). but that mailhog service isnt
                  in your compose file so its not running. how do you add it you ask?
[17:41] < rawtaz> you dont reference the .env file at all, docker automatically reads it and populates the environment.
[17:42] < physikoi> *processing*
[17:45] < rawtaz> you add your development mail server with this:  https://kopy.io/A0fck
[17:48] < physikoi> ohmygod. OK, so in docker-compose.yml, i might actually define, say, the mail server like this: `MAIL_SERVER: ${MAIL_SERVER:-mail.mysite.com}`. In development,
                    MAIL_SERVER is interpolated to "mailhog" since I might set `MAIL_SERVER=mailhog` in .env. However, deployment wouldn't include .env and so docker would get
                    mail.myite.com when building.
[17:48] < physikoi> Am i close?
[17:49] < physikoi> looking now
[17:49] < rawtaz> that would be correct yes, you are talking about making use of the default value for that environment variable
[17:50] < rawtaz> i would however not recommend to have a default value like that, as it's better to keep that type of variable as required and not have a default
[17:50] < rawtaz> also the syntax for default value is just - not :-
[17:54] < physikoi> ah, yeah, i mixed up bash and docker syntax
[17:56] < physikoi> In your example posted online, you invoke docker-compose with both docker-compose.override.yaml  and docker-compose.yaml in your development environment.
                    Deployment would then just somehow entail invoking docker-compose with docker-compose.yml only, right?
[17:57] < rawtaz> if you are running  docker-compose up  in the same dir as those files, it will automatically read them both, no need to specify either of them
[17:59] < physikoi> But deployment would need to exclude  docker-compose.override.yaml?
[17:59] < devslash> physikoi, not sure if someone recommentded this but you can set env vars in your compose script that can be read from php/node/server side code
[18:00] < rawtaz> physikoi: you dont need to version the override file, correct. it has nothing to do with the production, and all you version is what's relevant for production.
                  the fact that you add some additional sugar in your dev env is outside of the production sources
[18:00] < BtbN> Does a .dockerignore only work when it's in the top-level source dir?
[18:01] < BtbN> or context-dir. i.e. right next to the Dockerfile
[18:01] < physikoi> rawtaz: nice. i have a strategy. thank you so much. wow, what a lesson
[18:01] < rawtaz> yeah devslash made a good point - these env vars we're talking about here are simple environment variables that are injected into the actual containers. so they
                  are the end target for using them
[18:01] < rawtaz> physikoi: yw
[18:01] < rawtaz> physikoi: not that this is probably far from the best practice - it's just what *i* do :P
[18:01] < physikoi> devslash: something to think about for sure. entry script is what you're referring to, i think
[18:02] < physikoi> right, right.

------

* make the "default" values the production values
* the default values can live in the web app configuration
