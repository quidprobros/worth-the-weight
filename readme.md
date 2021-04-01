# What is Worth the Weight?

Just a website for *local* personal use. 



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
- [ ] basic graph to see progress
- [x] webroot is wrong!
- [x] optimize resource loading (js/css)
- [ ] setup htmx listen on "graph" sidebar
- [ ] return data should just be sums and diffs

# spring 4 (reach)
- [ ] store and track goals
- [ ] submit new foods
- [ ] show popup when user's been a way for a while


# security sprint
- [ ] use UUIDs instead of ids (?)
- [ ] doorway to manage walks

# OWASP Zap (passive scan)
- [x] X-Frame-Options Header Not Set
- [x] Server Leaks Information via "X-Powered-By" HTTP Response Header Field
- [x] X-Content-Type-Options Header Missing


# reach
* https://github.com/auraphp/Aura.Sql/blob/3.x/docs/getting-started.md
