# What is Worth the Weight?

A web application for tracking food consumption. You can also record whether you exercised on any given day. You can try out a demo right here: https://wtw.paxperscientiam.com/login

Worth the Weight is available to demo right here: https://wtw.paxperscientiam.com/login

## Note:
This will not work out of the box as db bootstrapping still needs to be setup. If you're interested in the progress, check out the dockerize branch.

# sprint 1
- [x] Add form to submit quantity of food, fractional measurement unit, food item, and date.
- [x] Add sqlite database support to store form data.
- [x] Add front end button to delete record.
- [x] Import food data
- [x] Invisibly add time
- [x] Display food name, not id when appending via js
- [x] Append action cell via JS too
- [ ] ~~Enable search~~
- [x] content enter not just tab
- [x] Make sure all db queries are prepared before executed
- [x] Finish integrating pop-up notices https://notifyjs.jpillora.com/

# sprint 2
- [x] integrate htmx for handling requests on client and updatin UI
- [ ] ~~add pagination to journal view~~
- [x] show point count for selected date in journal view
- [x] Show basic daily summary
- [x] click date to load view for today
- [x] validate (right form) and verify (sensical) data inputs
- [x] fix htmx conflict with displaying notifications
- [x] fix reactivity of deleting items for offcanvas journal
- [x] fix 'next page' bug
- [x] reset form after submit

# sprint 3
- [x] rework submit food log
- [x] remove cell editing
- [x] deleting from journal should update 'big picture'
- [x] use Eloqent models instead of PDO style
- [x] update code to use more fully html application state management
- [x] fix rounding error in big-picture display
- [x] when adding food, it should update journal properly
- [x] fix stats calculations
- [x] make sure carbon is using localtime
- [x] signed urls to prevent arbitrary access to resources
- [x] add a calendar that summarizes progress
- [x] webroot is wrong!
- [x] optimize resource loading (js/css)
- [x] finish swapping weird html solution for header triggered events (htmx)
- [x] click date to jump to page state
- [x] fix bug that causes wrong date to be highlighted on calendar
- [x] multiuser support with login system

# sprint 4
- [x] why is path deps triggering same elements multiple times??
- [x] dont send form data to views
- [x] have forms target iframes when JS not allowed
- [x] basic functionality without javascript
- [x] fix cant scroll button on small screens
- [x] use HX-Trigger to handle other actions stored in global App object
- [x] querystring for management of certain states
- [x] add tooltip with 'multiplier' explaination
- [x] reduce redundant queries
- [x] going through query log help me to find redudnant db queries
- [x] some kind of effect is needed to show page changing
- [x] fixed big hole in fuzzing block (redirects to urls with querystring were not signed and were not required to be signed)
- [x] iframe could be more helpful. present more information 
- [x] datatable gets messed up after adding new data. needs to be reinited

# Security and fault tolerance sprint
- [x] use signed urls (makes it difficult for endpoints to be abused)
- [x] doorway to manage walks (handled by HATEOS approach).
  - these are /home and /login
- [x] ensure that one user's records cannot be deleted by another user by only accessing *through* a user model (can happen through form manipulation)
- ## OWASP Zap (passive scan)
  - [x] X-Frame-Options Header Not Set
  - [x] Server Leaks Information via "X-Powered-By" HTTP Response Header Field
  - [x] X-Content-Type-Options Header Missing


# Reach goals
- [ ] test phinx for bootstrapping database
- [ ] when it comes to dockerizing, make sure the database has correct permissions
- [ ] dockerize (option)
- [ ] Allow individual cell editing
- [ ] Allow for storing and tracking goals 
- [ ] Allow for submitting new foods
- [ ] show popup when user's been a way for a while
- [ ] Write script to bootstrap fresh database
- [ ] cross site request forgery protection (security)
- [ ] Add help page
- [ ] Add a new-user link to help page.
- [ ] indicate how to add Secrets file



# Resources
- https://observatory.mozilla.org/ (security)
- https://blog.logrocket.com/the-ultimate-guide-to-iframes/ (iframes)
