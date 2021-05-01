# What is Worth the Weight?

Just a website for *local* personal use. 


# Bootstrapping
1. In project root, 



# meta
- [] bootstrap
  - [] mock food data


# sprint 1
- [x] Add form to submit quantity of food, measurement unit, food item, and date.
- [x] Add sqlite database support to store form data.
- [x] Add front end button to delete record.
- [x] Import food data
- [x] Allow individual cell editing
- [x] Invisibly add time
- [x] Display food name, not id when appending via js
- [x] Append action cell via JS too
- [x] Enable search
- [x] content enter not just tab
- [x] Make sure all db queries are prepared before executed
- [x] Finish integrating notices https://notifyjs.jpillora.com/

# sprint 2
- [x] integrate htmx for handling requesets on client and updatin UI
- [x] add pagination to journal view
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
- [ ] bootstrap script
- [ ] multiuser support

# spring 4 (reach)
- [ ] when to use flight mapped or not?
- [x] querystring for management of certain states
- [ ] store and track goals
- [ ] submit new foods
- [ ] show popup when user's been a way for a while
- [ ] add tooltip with 'multiplier' explaination
- [ ] reduce redundant queries
- [ ] going through query log help me to find redudnant db queries
- [ ] must have bootstrap

# Security sprint
- [x] use signed urls (makes it difficult for endpoints to be called arbitrarily)
- [ ] doorway to manage walks
- [ ] ensure that one user's records cannot be deleted by another user by only accessing *through* a user model

# OWASP Zap (passive scan)
- [x] X-Frame-Options Header Not Set
- [x] Server Leaks Information via "X-Powered-By" HTTP Response Header Field
- [x] X-Content-Type-Options Header Missing


# reach
* https://github.com/auraphp/Aura.Sql/blob/3.x/docs/getting-started.md
